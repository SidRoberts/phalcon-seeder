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

                $success = $this->db->createTable(
                    $model->getSource(),
                    null,
                    [
                        "columns" => $this->getColumns($model)
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
                $indexes = $this->getIndexes($model);

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
                $references = $this->getReferences($model);

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
                $data = $this->getInitialData($model);

                if (!$data) {
                    continue;
                }



                if ($eventsManager instanceof \Phalcon\Events\ManagerInterface) {
                    $eventsManager->fire("seeder:beforeCreateModelData", $model);
                }

                $modelClass = get_class($model);

                foreach ($data as $datum) {
                    $row = new $modelClass();

                    if (!$row->create($datum)) {
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
                $references = $this->getReferences($model);

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



    /**
     * @param  \Phalcon\Mvc\ModelInterface $model
     *
     * @return array
     */
    public function getColumns(\Phalcon\Mvc\ModelInterface $model)
    {
        $columns = [];

        $propertiesAnnotations = $this->annotations->getProperties(get_class($model));

        foreach ($propertiesAnnotations as $property => $propertyAnnotations) {
            if (!$propertyAnnotations->has('Column')) {
                continue;
            }

            $definition = [];

            $definition['primary']       = $propertyAnnotations->has("Primary");
            $definition['autoIncrement'] = $propertyAnnotations->has("Identity");

            $columnAnnotation = $propertyAnnotations->get("Column");

            $columnName = $columnAnnotation->getNamedParameter("column");
            if ($columnName === null) {
                $columnName = $property;
            }

            $type = $columnAnnotation->getNamedParameter("type");
            $definition['type'] = $this->getColumnTypeConstant($type);

            $nullable = $columnAnnotation->getNamedParameter("nullable");
            if ($nullable !== null) {
                $definition['notNull'] = !$nullable;
            }

            $default = $columnAnnotation->getNamedParameter("default");
            if ($default !== null) {
                $definition["default"] = $default;
            }

            $size = $columnAnnotation->getNamedParameter("size");
            if ($size !== null) {
                $definition['size'] = $size;
            }

            $columns[] = new \Phalcon\Db\Column($columnName, $definition);
        }

        return $columns;
    }

    /**
     * @param  \Phalcon\Mvc\ModelInterface $model
     *
     * @return array
     */
    public function getIndexes(\Phalcon\Mvc\ModelInterface $model)
    {
        $indexes = [];

        $classAnnotations = $this->annotations->get(get_class($model))->getClassAnnotations();

        if ($classAnnotations) {
            if ($classAnnotations->has("Index")) {
                $indexAnnotations = $classAnnotations->getAll("Index");

                foreach ($indexAnnotations as $indexAnnotation) {
                    $arguments = $indexAnnotation->getArguments();

                    $name    = $arguments[0];
                    $columns = $arguments[1];
                    $type    = isset($arguments[2]) ? $arguments[2] : null;

                    $indexes[] = new \Phalcon\Db\Index($name, $columns, $type);
                }
            }
        }

        return $indexes;
    }

    /**
     * @param  \Phalcon\Mvc\ModelInterface $model
     *
     * @return array
     */
    public function getReferences(\Phalcon\Mvc\ModelInterface $model)
    {
        $references = [];

        $classAnnotations = $this->annotations->get(get_class($model))->getClassAnnotations();

        if ($classAnnotations) {
            if ($classAnnotations->has("Reference")) {
                $referenceAnnotations = $classAnnotations->getAll("Reference");

                foreach ($referenceAnnotations as $referenceAnnotation) {
                    $arguments = $referenceAnnotation->getArguments();

                    $references[] = new \Phalcon\Db\Reference($arguments[0], $arguments[1]);
                }
            }
        }

        return $references;
    }

    /**
     * @param  \Phalcon\Mvc\ModelInterface $model
     *
     * @return array
     */
    public function getInitialData(\Phalcon\Mvc\ModelInterface $model)
    {
        $data = [];

        $classAnnotations = $this->annotations->get(get_class($model))->getClassAnnotations();

        if ($classAnnotations) {
            if ($classAnnotations->has("Data")) {
                $dataAnnotations = $classAnnotations->getAll("Data");

                foreach ($dataAnnotations as $dataAnnotation) {
                    $data[] = $dataAnnotation->getArgument(0);
                }
            }

            if ($classAnnotations->has("DataJson")) {
                $guzzle = new \GuzzleHttp\Client();

                $dataJsonAnnotations = $classAnnotations->getAll("DataJson");

                foreach ($dataJsonAnnotations as $dataJsonAnnotation) {
                    $url = $dataJsonAnnotation->getArgument(0);

                    $response = $guzzle->get($url);

                    $json = json_decode($response->getBody(), true);

                    foreach ($json as $datum) {
                        $data[] = $datum;
                    }
                }
            }
        }

        return $data;
    }

    protected function getColumnTypeConstant($type)
    {
        $columnTypes = [
            "integer"    => \Phalcon\Db\Column::TYPE_INTEGER,
            "date"       => \Phalcon\Db\Column::TYPE_DATE,
            "varchar"    => \Phalcon\Db\Column::TYPE_VARCHAR,
            "decimal"    => \Phalcon\Db\Column::TYPE_DECIMAL,
            "datetime"   => \Phalcon\Db\Column::TYPE_DATETIME,
            "char"       => \Phalcon\Db\Column::TYPE_CHAR,
            "text"       => \Phalcon\Db\Column::TYPE_TEXT,
            "float"      => \Phalcon\Db\Column::TYPE_FLOAT,
            "boolean"    => \Phalcon\Db\Column::TYPE_BOOLEAN,
            "double"     => \Phalcon\Db\Column::TYPE_DOUBLE,
            "tinyblob"   => \Phalcon\Db\Column::TYPE_TINYBLOB,
            "blob"       => \Phalcon\Db\Column::TYPE_BLOB,
            "mediumblob" => \Phalcon\Db\Column::TYPE_MEDIUMBLOB,
            "longblob"   => \Phalcon\Db\Column::TYPE_LONGBLOB,
            "biginteger" => \Phalcon\Db\Column::TYPE_BIGINTEGER,
            "json"       => \Phalcon\Db\Column::TYPE_JSON,
            "jsonb"      => \Phalcon\Db\Column::TYPE_JSONB
        ];

        return $columnTypes[$type];
    }
}
