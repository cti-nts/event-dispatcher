<?php

declare(strict_types=1);

namespace Infrastructure\Event\Adapter\Postgres;

use Application\Event\Dispatcher;
use Application\Event\Filter;
use Application\Event\Store as EventStore;
use Exception;
use PDO;
use PDOException;

class Store implements EventStore
{
    protected PDO $con;

    protected bool $listenerSetUp = false;

    protected const LISTEN_TIMEOUT = 1000;

    protected const EVENT_NOTIFY_PROCEDURE_SQL = "
        CREATE OR REPLACE FUNCTION public.event_notify()
        RETURNS trigger
        AS \$function\$
        BEGIN
            IF NEW.dispatched = false %%filter_matcher%% THEN
                PERFORM pg_notify('event', NEW.id::text);
            END IF;
            RETURN NULL;
        END;
        \$function\$
        LANGUAGE plpgsql;
    ";

    protected const EVENT_NOTIFY_TRIGGER_SQL = "
        CREATE TRIGGER trigger_on_event_insert AFTER INSERT ON event
        FOR EACH ROW EXECUTE PROCEDURE event_notify();
    ";

    protected const UPDATE_EVENT_SQL = "
        UPDATE event SET dispatched = true, dispatched_at = NOW() WHERE id = :id AND dispatched = false;
    ";

    protected const SELECT_UNDISPATCHED_EVENTS_SQL = "
        SELECT * FROM event AS NEW WHERE NEW.dispatched = false %%filter_matcher%% ORDER BY id LIMIT %%polling_select_limit%%;
    ";

    public function __construct(protected readonly ?Filter $filter = null, protected readonly bool $setupListener = false)
    {
        $dsn = "pgsql:host=" . getenv('STORE_DB_HOST') . ";port=" . (getenv('DB_PORT') ?: '5432') . ";dbname=" . getenv('STORE_DB_NAME');
        $this->con = new PDO($dsn, getenv('STORE_DB_USER'), getenv('STORE_DB_PASSWORD'));

        if ($setupListener) {
            $this->setUpListener();
        }
    }

    public function listen(Dispatcher $dispatcher): void
    {
        if (!$this->listenerSetUp) {
            throw new Exception('Listener is not set up!');
        }

        $dispatcher->pollProducer();
        $notification = $this->con->pgsqlGetNotify(PDO::FETCH_ASSOC, self::LISTEN_TIMEOUT);
        if (!$notification) {
            return;
        }

        $eventId = $notification['payload'];
        $stmt = $this->con->prepare("SELECT * FROM event WHERE id = :id");
        $stmt->execute(['id' => $eventId]);

        $eventData = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$eventData) {
            error_log("Event {$eventId} not found in database");
            return;
        }

        $eventData['data'] = json_decode((string)$eventData['data'], true);
        echo "Received notification for event with id " . $eventData['id'] . "\n";
        $this->dispatch(eventData: $eventData, dispatcher: $dispatcher);
    }

    public function dispatchAllUndispatched(Dispatcher $dispatcher): void
    {
        $batchSize = (int)(getenv('POLLING_DB_BATCH_SIZE') ?: '100');
        $maxEvents = (int)(getenv('POLLING_DB_SELECT_LIMIT') ?: '10000');
        $processedCount = 0;
        $lastId = 0;

        echo "Starting batch processing (batch size: {$batchSize}, max: {$maxEvents})...\n";

        while ($processedCount < $maxEvents) {
            $batch = $this->fetchUndispatchedBatch($lastId, $batchSize);

            if ($batch === []) {
                echo "No more undispatched events found.\n";
                break;
            }

            foreach ($batch as $eventData) {
                $eventData['data'] = json_decode((string)$eventData['data'], true);
                echo "Dispatching undispatched event with id " . $eventData['id'] . "\n";
                $this->dispatch(eventData: $eventData, dispatcher: $dispatcher);

                $lastId = max($lastId, (int)$eventData['id']);
                $processedCount++;
            }

            // Allow Kafka producer to poll between batches
            $dispatcher->pollProducer();

            echo "Processed {$processedCount} events so far...\n";
        }

        echo "Batch processing complete. Total: {$processedCount}\n";
    }

    /**
     * Fetch a batch of undispatched events using cursor-based pagination.
     */
    protected function fetchUndispatchedBatch(int $lastId, int $limit): array
    {
        $filterMatcher = str_replace(
            "%%filter_matcher%%",
            $this->getFilterMatcher(),
            self::SELECT_UNDISPATCHED_EVENTS_SQL
        );
        $query = str_replace(
            "%%polling_select_limit%%",
            (string)$limit,
            $filterMatcher
        );

        // Add cursor-based pagination
        $query = str_replace(
            "WHERE NEW.dispatched = false",
            "WHERE NEW.dispatched = false AND NEW.id > :last_id",
            $query
        );

        $stmt = $this->con->prepare($query);
        $stmt->execute(['last_id' => $lastId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function getFilterMatcher(): string
    {
        if ($this->filter === null) {
            return '';
        }

        if (!($matcherStr = $this->filter->getSqlMatcher())) {
            return '';
        }

        return "AND (" . $matcherStr . ")";
    }

    protected function setUpListener(): void
    {
        $this->con->exec(str_replace("%%filter_matcher%%", $this->getFilterMatcher(), self::EVENT_NOTIFY_PROCEDURE_SQL));
        try {
            $this->con->exec(self::EVENT_NOTIFY_TRIGGER_SQL);
        } catch (PDOException $e) {
            // SQLSTATE[42710]: Duplicate object: 7 ERROR:  trigger "trigger_on_event_insert" for relation "event"
            // @phpstan-ignore equal.notAllowed
            if ($e->getCode() == '42710') {
                echo "Trigger already defined: " . $e->getMessage() . "\n";
            } else {
                throw $e;
            }
        }

        $this->con->exec("LISTEN event;");
        $this->listenerSetUp = true;
    }

    protected function dispatch(array $eventData, Dispatcher $dispatcher): void
    {
        if (!$dispatcher->dispatch(eventData: $eventData)) {
            echo "Event with id " . $eventData['id'] . " skipped by dispatcher\n";
        }
    }

    public function dispatchSuccessCallback(string $eventId): void
    {
        try {
            $this->con->beginTransaction();

            $statement = $this->con->prepare(self::UPDATE_EVENT_SQL);
            $statement->execute(['id' => $eventId]);

            // Verify the update actually affected a row
            if ($statement->rowCount() === 0) {
                $this->con->rollBack();
                throw new Exception("Failed to update event {$eventId}: event not found or already dispatched");
            }

            $this->con->commit();
        } catch (PDOException $e) {
            if ($this->con->inTransaction()) {
                $this->con->rollBack();
            }

            // Log the error but don't crash - the event is already in Kafka
            error_log("Failed to mark event {$eventId} as dispatched: " . $e->getMessage());
            throw $e;
        }
    }
}
