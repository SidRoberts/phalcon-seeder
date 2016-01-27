<?php

namespace Sid\Phalcon\Seeder;

class Seeder extends \Phalcon\Di\Injectable implements \Phalcon\Events\EventsAwareInterface
{
    /**
     * @var \Phalcon\Events\ManagerInterface
     */
    protected $_eventsManager;



    /**
     * @throws Exception
     */
    public function __construct()
    {
        $di = $this->getDI();
        if (!($di instanceof \Phalcon\DiInterface)) {
            throw new Exception("A dependency injection object is required to access internal services");
        }
    }



    /**
     * @return \Phalcon\Events\ManagerInterface
     */
    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    /**
     * @param \Phalcon\Events\ManagerInterface $eventsManager
     */
    public function setEventsManager(\Phalcon\Events\ManagerInterface $eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }



    /**
     * @param array $models
     *
     * @throws \Exception
     */
    public function seed($models)
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
     * @param array $models
     *
     * @throws \Exception
     */
    public function drop($models)
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
     * @param array $models
     *
     * @throws Exception
     */
    protected function createTables($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateTable", $model);
            }

            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $success = $this->db->createTable(
                $model->getSource(),
                null,
                [
                    "columns" => $modelAnnotations->getColumns(),
                    "options" => $modelAnnotations->getTableOptions()
                ]
            );

            if (!$success) {
                throw new Exception("Table `" . $model->getSource() . "` not created.");
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateTable", $model);
            }
        }
    }

    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function createModelIndexes($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $indexes = $modelAnnotations->getIndexes();

            if (!$indexes) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateModelIndexes", $model);
            }

            foreach ($indexes as $index) {
                $success = $this->db->addIndex($model->getSource(), null, $index);

                if (!$success) {
                    throw new Exception("Index `" . $index->getName() . "` on `" . $model->getSource() . "` not created.");
                }
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelIndexes", $model);
            }
        }
    }

    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function createModelReferences($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            $modelAnnotations = new \Sid\Phalcon\Seeder\Annotations($model);

            $references = $modelAnnotations->getReferences();

            if (!$references) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeCreateModelReferences", $model);
            }

            foreach ($references as $reference) {
                $success = $this->db->addForeignKey($model->getSource(), $reference->getSchemaName(), $reference);

                if (!$success) {
                    throw new Exception("Reference `" . $reference->getName() . "` on `" . $model->getSource() . "` not created.");
                }
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelReferences", $model);
            }
        }
    }

    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function createModelData($models)
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
                    throw new Exception("Data not created for `" . $model->getSource() . "`.");
                }
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterCreateModelData", $model);
            }
        }
    }



    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function dropModelReferences($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if (!$this->db->tableExists($model->getSource())) {
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
                $success = $this->db->dropForeignKey($model->getSource(), $reference->getSchemaName(), $reference->getName());

                if (!$success) {
                    throw new Exception("Reference `" . $reference->getName() . "` on `" . $model->getSource() . "` not dropped.");
                }
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterDropModelReferences", $model);
            }
        }
    }

    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function truncateTables($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if (!$this->db->tableExists($model->getSource())) {
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
                throw new Exception("Table `" . $model->getSource() . "` not truncated.");
            }

            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:afterTruncateTable", $model);
            }
        }
    }

    /**
     * @param array $models
     *
     * @throws Exception
     */
    protected function dropTables($models)
    {
        $eventsManager = $this->getEventsManager();

        foreach ($models as $model) {
            if (!$this->db->tableExists($model->getSource())) {
                continue;
            }



            if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                $eventsManager->fire("seeder:beforeDropTable", $model);
            }

            $success = $this->db->dropTable($model->getSource());

            if (!$success) {
                throw new Exception("Table `" . $model->getSource() . "` not dropped.");
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
     *
     * @param array $models
     *
     * @return array
     */
    protected function orderForSeedingInitialData(array $models)
    {
        $modelsWaitingToBeSorted = [];

        foreach ($models as $model) {
            $modelsWaitingToBeSorted[$model->getSource()] = $model;
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
