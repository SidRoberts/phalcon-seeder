<?php

namespace Sid\Phalcon\Seeder;

use Phalcon\Di\Injectable;
use Phalcon\Di\DiInterface;
use Phalcon\Events\EventsAwareInterface;

class Seeder extends Injectable implements EventsAwareInterface
{
    /**
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $eventsManager;



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



    public function getEventsManager() : \Phalcon\Events\ManagerInterface
    {
        return $this->eventsManager;
    }

    public function setEventsManager(\Phalcon\Events\ManagerInterface $eventsManager)
    {
        $this->eventsManager = $eventsManager;
    }



    /**
     * @throws \Exception
     */
    public function seed(array $models)
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
    public function drop(array $models)
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
    protected function createTables(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateTable", $model);
            }

            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

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

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateTable", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelIndexes(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $indexes = $modelAnnotations->getIndexes();

            if (!$indexes) {
                continue;
            }



            $source = $model->getSource();



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
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

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelIndexes", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelReferences(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $references = $modelAnnotations->getReferences();

            if (!$references) {
                continue;
            }



            $source = $model->getSource();



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
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

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelReferences", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function createModelData(array $models)
    {
        $eventsManager = $this->getEventsManager();

        $modelsOrderedForSeedingInitialData = $this->orderForSeedingInitialData($models);

        foreach ($modelsOrderedForSeedingInitialData as $model) {
            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $data = $modelAnnotations->getInitialData();

            if (!$data) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
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

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelData", $model);
            }
        }
    }



    /**
     * @throws Exception
     */
    protected function dropModelReferences(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $references = $modelAnnotations->getReferences();

            if (!$references) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
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

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterDropModelReferences", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function truncateTables(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeTruncateTable", $model);
            }

            $rows = $model::find();

            foreach ($rows as $row) {
                $row->delete();
            }

            $success = ($model::count() == 0);

            if (!$success) {
                throw new Exception(
                    sprintf(
                        "Table `%s` not truncated.",
                        $source
                    )
                );
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterTruncateTable", $model);
            }
        }
    }

    /**
     * @throws Exception
     */
    protected function dropTables(array $models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $source = $model->getSource();



            if (!$this->db->tableExists($source)) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeDropTable", $model);
            }

            $success = $this->db->dropTable(
                $source
            );

            if (!$success) {
                throw new Exception(
                    sprintf(
                        "Table `%s` not dropped.",
                        $source
                    )
                );
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterDropTable", $model);
            }
        }
    }



    /**
     * This method is a complicated mess and will get rewritten at some point.
     * The problem it addresses is: when you seed the initial data, you can
     * run into problems with foreign keys. For example, you might have two
     * models: Posts and Comments. A Comment depends on a Post and so initial
     * data needs to be added to Posts first and then to Comments. If you try to
     * write to Comments first, the foreign key/reference will cause it to fail.
     * This method sorts the models in an order that ensures that models come
     * after any other models they depend on.
     */
    protected function orderForSeedingInitialData(array $models) : array
    {
        $modelsWaitingToBeSorted = [];

        foreach ($models as $model) {
            $source = $model->getSource();

            $modelsWaitingToBeSorted[$source] = $model;
        }

        $newOrder = [];

        while (count($modelsWaitingToBeSorted) > 0) {
            foreach ($modelsWaitingToBeSorted as $source => $model) {
                $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

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
