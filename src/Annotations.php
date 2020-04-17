<?php

namespace Sid\Phalcon\Seeder;

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Db\Reference;
use Phalcon\Di\Injectable;
use Phalcon\Di\DiInterface;
use Phalcon\Mvc\ModelInterface;

class Annotations extends Injectable
{
    /**
     * @var ModelInterface
     */
    protected $model;



    /**
     * @throws Exception
     */
    public function __construct(ModelInterface $model)
    {
        $this->model = $model;



        $di = $this->getDI();

        if (!($di instanceof DiInterface)) {
            throw new Exception(
                "A dependency injection object is required to access internal services"
            );
        }
    }



    public function getColumns() : array
    {
        $columns = [];

        $modelClass = get_class($this->model);

        $propertiesAnnotations = $this->annotations->getProperties($modelClass);

        foreach ($propertiesAnnotations as $property => $propertyAnnotations) {
            if (!$propertyAnnotations->has("Column")) {
                continue;
            }

            $definition = [];

            $definition["primary"]       = $propertyAnnotations->has("Primary");
            $definition["autoIncrement"] = $propertyAnnotations->has("Identity");

            $columnAnnotation = $propertyAnnotations->get("Column");

            $columnName = $columnAnnotation->getNamedParameter("column");
            if ($columnName === null) {
                $columnName = $property;
            }

            $type = $columnAnnotation->getNamedParameter("type");
            $definition["type"] = $this->getColumnTypeConstant($type);

            $nullable = $columnAnnotation->getNamedParameter("nullable");
            if ($nullable !== null) {
                $definition["notNull"] = !$nullable;
            }

            $default = $columnAnnotation->getNamedParameter("default");
            if ($default !== null) {
                $definition["default"] = $default;
            }

            $size = $columnAnnotation->getNamedParameter("size");
            if ($size !== null) {
                $definition["size"] = $size;
            }

            $columns[] = new Column($columnName, $definition);
        }

        return $columns;
    }

    public function getTableOptions() : array
    {
        $classAnnotations = $this->getClassAnnotations();

        if (!$classAnnotations) {
            return [];
        }



        $options = [];

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

        return $options;
    }

    public function getIndexes() : array
    {
        $classAnnotations = $this->getClassAnnotations();

        if (!$classAnnotations || !$classAnnotations->has("Index")) {
            return [];
        }



        $indexes = [];

        $indexAnnotations = $classAnnotations->getAll("Index");

        foreach ($indexAnnotations as $indexAnnotation) {
            $arguments = $indexAnnotation->getArguments();

            $name    = (string) $arguments[0];
            $columns = $arguments[1];
            $type    = (string) ($arguments[2] ?? "");

            $indexes[] = new Index($name, $columns, $type);
        }

        return $indexes;
    }

    public function getReferences() : array
    {
        $classAnnotations = $this->getClassAnnotations();

        if (!$classAnnotations || !$classAnnotations->has("Reference")) {
            return [];
        }



        $references = [];

        $referenceAnnotations = $classAnnotations->getAll("Reference");

        foreach ($referenceAnnotations as $referenceAnnotation) {
            $arguments = $referenceAnnotation->getArguments();

            $name       = (string) $arguments[0];
            $definition = $arguments[1];

            $references[] = new Reference($name, $definition);
        }

        return $references;
    }

    public function getInitialData() : array
    {
        $classAnnotations = $this->getClassAnnotations();

        if (!$classAnnotations) {
            return [];
        }



        $data = [];

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

                $json = json_decode(
                    $response->getBody(),
                    true
                );

                foreach ($json as $datum) {
                    $data[] = $datum;
                }
            }
        }

        return $data;
    }

    protected function getColumnTypeConstant(string $type) : int
    {
        $columnTypes = [
            "integer"    => Column::TYPE_INTEGER,
            "date"       => Column::TYPE_DATE,
            "varchar"    => Column::TYPE_VARCHAR,
            "decimal"    => Column::TYPE_DECIMAL,
            "datetime"   => Column::TYPE_DATETIME,
            "char"       => Column::TYPE_CHAR,
            "text"       => Column::TYPE_TEXT,
            "float"      => Column::TYPE_FLOAT,
            "boolean"    => Column::TYPE_BOOLEAN,
            "double"     => Column::TYPE_DOUBLE,
            "tinyblob"   => Column::TYPE_TINYBLOB,
            "blob"       => Column::TYPE_BLOB,
            "mediumblob" => Column::TYPE_MEDIUMBLOB,
            "longblob"   => Column::TYPE_LONGBLOB,
            "biginteger" => Column::TYPE_BIGINTEGER,
            "json"       => Column::TYPE_JSON,
            "jsonb"      => Column::TYPE_JSONB,
        ];

        return $columnTypes[$type];
    }

    protected function getClassAnnotations()
    {
        $modelClass = get_class($this->model);

        $classAnnotations = $this->annotations->get($modelClass)->getClassAnnotations();

        return $classAnnotations;
    }
}
