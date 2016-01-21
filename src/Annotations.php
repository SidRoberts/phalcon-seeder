<?php

namespace Sid\Phalcon\Seeder;

class Annotations extends \Phalcon\Di\Injectable
{
    /**
     * @var \Phalcon\Mvc\ModelInterface
     */
    protected $model;


    /**
     * @param \Phalcon\Mvc\ModelInterface $model
     *
     * @throws Exception
     */
    public function __construct(\Phalcon\Mvc\ModelInterface $model)
    {
        $this->model = $model;

        $di = $this->getDI();
        if (!($di instanceof \Phalcon\DiInterface)) {
            throw new Exception("A dependency injection object is required to access internal services");
        }
    }



    /**
     * @return array
     */
    public function getColumns()
    {
        $columns = [];

        $propertiesAnnotations = $this->annotations->getProperties(get_class($this->model));

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
     * @return array
     */
    public function getTableOptions()
    {
        $options = [];

        $classAnnotations = $this->annotations->get(get_class($this->model))->getClassAnnotations();

        if ($classAnnotations) {
            if ($classAnnotations->has("Engine")) {
                $engineAnnotation = $classAnnotations->get("Engine");

                $options["ENGINE"] = $engineAnnotation->getArgument(0);
            }

            if ($classAnnotations->has("AutoIncrement")) {
                $autoIncrementAnnotation = $classAnnotations->get("AutoIncrement");

                $options["AUTO_INCREMENT"] = $autoIncrementAnnotation->getArgument(0);
            }

            if ($classAnnotations->has("Collation")) {
                $collationAnnotation = $classAnnotations->get("Collation");

                $options["TABLE_COLLATION"] = $collationAnnotation->getArgument(0);
            }
        }

        return $options;
    }

    /**
     * @return array
     */
    public function getIndexes()
    {
        $indexes = [];

        $classAnnotations = $this->annotations->get(get_class($this->model))->getClassAnnotations();

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
     * @return array
     */
    public function getReferences()
    {
        $references = [];

        $classAnnotations = $this->annotations->get(get_class($this->model))->getClassAnnotations();

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
     * @return array
     */
    public function getInitialData()
    {
        $data = [];

        $classAnnotations = $this->annotations->get(get_class($this->model))->getClassAnnotations();

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

    /**
     * @param string $type
     *
     * @return integer
     */
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
