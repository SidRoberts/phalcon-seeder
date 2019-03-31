<?php

namespace Sid\Phalcon\Seeder\Tests;

use Codeception\TestCase\Test;
use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Di;
use Sid\Phalcon\Seeder\Annotations;
use Users;

class AnnotationsTest extends Test
{
    public function _before()
    {
        Di::reset();

        $this->di = new \Phalcon\Di\FactoryDefault\Cli();
    }



    public function testGetColumns()
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $columns = $annotations->getColumns();

        $this->assertCount(
            3,
            $columns
        );



        $userIdColumn = $columns[0];

        $this->assertEquals(
            "userID",
            $userIdColumn->getName()
        );

        $this->assertTrue(
            $userIdColumn->isPrimary()
        );

        $this->assertTrue(
            $userIdColumn->isNotNull()
        );

        $this->assertTrue(
            $userIdColumn->isAutoIncrement()
        );

        $this->assertEquals(
            Column::TYPE_BIGINTEGER,
            $userIdColumn->getType()
        );



        $emailAddressColumn = $columns[1];

        $this->assertEquals(
            "emailAddress",
            $emailAddressColumn->getName()
        );

        $this->assertFalse(
            $emailAddressColumn->isPrimary()
        );

        $this->assertTrue(
            $emailAddressColumn->isNotNull()
        );

        $this->assertFalse(
            $emailAddressColumn->isAutoIncrement()
        );

        $this->assertEquals(
            255,
            $emailAddressColumn->getSize()
        );

        $this->assertEquals(
            Column::TYPE_VARCHAR,
            $emailAddressColumn->getType()
        );



        $passwordColumn = $columns[2];

        $this->assertEquals(
            "password",
            $passwordColumn->getName()
        );

        $this->assertFalse(
            $passwordColumn->isPrimary()
        );

        $this->assertTrue(
            $passwordColumn->isNotNull()
        );

        $this->assertFalse(
            $passwordColumn->isAutoIncrement()
        );

        $this->assertEquals(
            100,
            $passwordColumn->getSize()
        );

        $this->assertEquals(
            Column::TYPE_VARCHAR,
            $passwordColumn->getType()
        );
    }



    public function testGetIndexes()
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $indexes = $annotations->getIndexes();

        $this->assertCount(
            1,
            $indexes
        );

        $index = $indexes[0];

        $this->assertEquals(
            "emailAddress",
            $index->getName()
        );

        $this->assertEquals(
            [
                "emailAddress",
            ],
            $index->getColumns()
        );
    }



    public function testGetReferences()
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $references = $annotations->getReferences();

        $this->assertCount(
            1,
            $references
        );

        $reference = $references[0];

        $this->assertEquals(
            "Users_userID",
            $reference->getName()
        );

        $this->assertEquals(
            "Posts",
            $reference->getReferencedTable()
        );

        $this->assertEquals(
            [
                "userID",
            ],
            $reference->getColumns()
        );

        $this->assertEquals(
            [
                "userID",
            ],
            $reference->getReferencedColumns()
        );
    }



    public function testGetInitialData()
    {
        $user = new Users();

        $annotations = new Annotations($user);

        $initialData = $annotations->getInitialData();

        $this->assertCount(
            2,
            $initialData
        );

        $this->assertEquals(
            [
                [
                    "userID"       => 1,
                    "emailAddress" => "sid1@sidroberts.co.uk",
                    "password"     => "S3CR3T",
                ],

                [
                    "userID"       => 2,
                    "emailAddress" => "sid2@sidroberts.co.uk",
                    "password"     => "P4SSW0RD",
                ],
            ],
            $initialData
        );
    }
}
