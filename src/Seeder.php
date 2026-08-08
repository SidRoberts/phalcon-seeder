<?php

namespace Sid\Phalcon\Seeder;

use Phalcon\Di\Injectable;
use Phalcon\Di\DiInterface;
use Phalcon\Events\EventsAwareInterface;
use Phalcon\Events\ManagerInterface as EventsManagerInterface;
use Sid\Phalcon\Seeder\Annotations as SeederAnnotations;

class Seeder extends Injectable implements EventsAwareInterface
{
    protected EventsManagerInterface $eventsManager;



    /**
     * @throws Exception
     */
    public function __construct()
    {
        $di = $this->getDI();

        if (!($di instanceof DiInterface)) {
            throw new Exception(
                "A dependency injection object is required to access internal services"
            );
        }
    }



    public function getEventsManager(): EventsManagerInterface
    {
        return $this->eventsManager;
    }

    public function setEventsManager(EventsManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }



    /**
     * @throws \Exception
     */
    public function seed(array $models): void
    {
        try {
            $this->db->begin();

            $this->createTables($models);

            $this->createModelIndexes($models);

            $this->createModelReferences($models);

            $this->createModelData($models);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();

            throw $e;
        }
    }



    /**
     * @throws \Exception
     */
    public function drop(array $models): void
    {
        try {
            $this->db->begin();

            $this->dropModelReferences($models);

            $this->truncateTables($models);

            $this->dropTables($models);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();

            throw $e;
        }
    }



    /**
     * @throws Exception
     */
    protected function createTables(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateTable", $model);
            }

            $modelAnnotations = new SeederAnnotations($model);

            $source = $model->getSource();

            $success = $this->db->createTable(
                $source,
                null,
                [
                    "columns" => $modelAnnotations->getColumns(),
                    "options" => $modelAnnotations->getTableOptions(),
                ]
            );

            if (!$success) {
                throw new Exception(
                    sprintf(
                        "Table `%s` not created.",
                        $source
                    )
                );
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterCreateTable", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelIndexes(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new SeederAnnotations($model);

            $indexes = $modelAnnotations->getIndexes();

            if (!$indexes) {
                continue;
            }



            $source = $model->getSource();



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateModelIndexes", $model);
            }

            foreach ($indexes as $index) {
                $success = $this->db->addIndex($source, null, $index);

                if (!$success) {
                    throw new Exception(
                        sprintf(
                            "Index `%s` on `%s` not created.",
                            $index->getName(),
                            $source
                        )
                    );
                }
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelIndexes", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelReferences(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new SeederAnnotations($model);

            $references = $modelAnnotations->getReferences();

            if (!$references) {
                continue;
            }



            $source = $model->getSource();



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateModelReferences", $model);
            }

            foreach ($references as $reference) {
                $success = $this->db->addForeignKey(
                    $source,
                    $reference->getSchemaName(),
                    $reference
                );

                if (!$success) {
                    throw new Exception(
                        sprintf(
                            "Reference `%s` on `%s` not created.",
                            $reference->getName(),
                            $source
                        )
                    );
                }
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelReferences", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelData(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        $modelsOrderedForSeedingInitialData = $this->orderForSeedingInitialData($models);

        foreach ($modelsOrderedForSeedingInitialData as $model) {
            $modelAnnotations = new SeederAnnotations($model);

            $data = $modelAnnotations->getInitialData();

            if (!$data) {
                continue;
            }



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateModelData", $model);
            }

            $modelClass = get_class($model);

            foreach ($data as $datum) {
                $row = new $modelClass();

                $row->assign($datum);

                if (!$row->create()) {
                    throw new Exception(
                        sprintf(
                            "Data not created for `%s`.",
                            $model->getSource()
                        )
                    );
                }
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelData", $model);
            }
        }
    }



    /**
     * @throws Exception
     */
    protected function dropModelReferences(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            $modelAnnotations = new SeederAnnotations($model);

            $references = $modelAnnotations->getReferences();

            if (!$references) {
                continue;
            }



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeDropModelReferences", $model);
            }

            foreach ($references as $reference) {
                $success = $this->db->dropForeignKey(
                    $source,
                    $reference->getSchemaName(),
                    $reference->getName()
                );

                if (!$success) {
                    throw new Exception(
                        sprintf(
                            "Reference `%s` on `%s` not dropped.",
                            $reference->getName(),
                            $source
                        )
                    );
                }
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterDropModelReferences", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function truncateTables(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeTruncateTable", $model);
            }

            $rows = $model::find();

            foreach ($rows as $row) {
                $row->delete();
            }

            $success = ($model::count() === 0);

            if (!$success) {
                throw new Exception(
                    sprintf(
                        "Table `%s` not truncated.",
                        $source
                    )
                );
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterTruncateTable", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function dropTables(array $models): void
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:beforeDropTable", $model);
            }

            $success = $this->db->dropTable($source);

            if (!$success) {
                throw new Exception(
                    sprintf(
                        "Table `%s` not dropped.",
                        $source
                    )
                );
            }

            if ($eventsManager instanceof EventsManagerInterface) {
                $eventsManager->fire("seeder:afterDropTable", $model);
            }
        }
    }



    /**
     * This method is a complicated mess and will get rewritten at some point.
     * The problem it addresses is: when you seed the initial data, you can
     * run into problems with foreign keys.
     *
     * For example, if `Comment` depends on `Post`, then `Post` must be seeded
     * first. This method sorts the given models so that each model appears only
     * after all models it references (via foreign keys) have been placed
     * earlier in the order.
     */
    protected function orderForSeedingInitialData(array $models): array
    {
        $modelsWaitingToBeSorted = [];

        foreach ($models as $model) {
            $source = $model->getSource();

            $modelsWaitingToBeSorted[$source] = $model;
        }

        $newOrder = [];

        while (count($modelsWaitingToBeSorted) > 0) {
            foreach ($modelsWaitingToBeSorted as $source => $model) {
                $modelAnnotations = new SeederAnnotations($model);

                $references = $modelAnnotations->getReferences();

                foreach ($references as $reference) {
                    if (in_array($reference->getReferencedTable(), array_keys($modelsWaitingToBeSorted))) {
                        // A referenced table is still waiting, so keep this one in.
                        continue 2;
                    }
                }

                $newOrder[] = $model;
                unset($modelsWaitingToBeSorted[$source]);
            }
        }

        return $newOrder;
    }
}
