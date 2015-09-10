<?php

namespace Sid\Phalcon\Seeder;

class Seeder extends \Phalcon\Di\Injectable implements \Phalcon\Events\EventsAwareInterface
{
    protected $_eventsManager;



    public function __construct()
    {
        $di = $this->getDI();
        if (!($di instanceof \Phalcon\DiInterface)) {
            throw new \Sid\Phalcon\Seeder\Exception("A dependency injection object is required to access internal services");
        }
    }



    public function getEventsManager()
    {
        return $this->_eventsManager;
    }

    public function setEventsManager(\Phalcon\Events\ManagerInterface $eventsManager)
    {
        $this->_eventsManager = $eventsManager;
    }



    /**
     * @param array $models
     */
    public function seed($models)
    {
        $eventsManager = $this->getEventsManager();

        try {
            $this->db->begin();

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
                    throw new \Sid\Phalcon\Seeder\Exception("Table `" . $model->getSource() . "` not created.");
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterCreateTable", $model);
                }
            }

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
                        throw new \Sid\Phalcon\Seeder\Exception("Index `" . $index->getName() . "` on `" . $model->getSource() . "` not created.");
                    }
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterCreateModelIndexes", $model);
                }
            }

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
                        throw new \Sid\Phalcon\Seeder\Exception("Reference `" . $reference->getName() . "` on `" . $model->getSource() . "` not created.");
                    }
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterCreateModelReferences", $model);
                }
            }

            foreach ($models as $model) {
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
                        throw new \Sid\Phalcon\Seeder\Exception("Data not created for `" . $model->getSource() . "`.");
                    }
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterCreateModelData", $model);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();

            throw $e;
        }
    }



    /**
     * @param array $models
     */
    public function drop($models)
    {
        $eventsManager = $this->getEventsManager();

        try {
            $this->db->begin();

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
                        throw new \Sid\Phalcon\Seeder\Exception("Reference `" . $reference->getName() . "` on `" . $model->getSource() . "` not dropped.");
                    }
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterDropModelReferences", $model);
                }
            }

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
                    throw new \Sid\Phalcon\Seeder\Exception("Table `" . $model->getSource() . "` not truncated.");
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterTruncateTable", $model);
                }
            }

            foreach ($models as $model) {
                if (!$this->db->tableExists($model->getSource())) {
                    continue;
                }



                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:beforeDropTable", $model);
                }

                $success = $this->db->dropTable($model->getSource());

                if (!$success) {
                    throw new \Sid\Phalcon\Seeder\Exception("Table `" . $model->getSource() . "` not dropped.");
                }

                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:afterDropTable", $model);
                }
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollback();

            throw $e;
        }
    }
}
