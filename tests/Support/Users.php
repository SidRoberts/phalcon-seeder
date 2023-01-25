<?php

namespace Tests\Support;

use Phalcon\Mvc\Model;

/**
 * @Index("emailAddress", ["emailAddress"])
 *
 * @Reference(
 *     "Users_userID",
 *     {
 *         referencedTable="Posts",
 *         columns=["userID"],
 *         referencedColumns=["userID"]
 *     }
 * )
 *
 * @Data(
 *     {
 *         userID=1,
 *         emailAddress="sid1@sidroberts.co.uk",
 *         password="S3CR3T"
 *     }
 * )
 *
 * @Data(
 *     {
 *         userID=2,
 *         emailAddress="sid2@sidroberts.co.uk",
 *         password="P4SSW0RD"
 *     }
 * )
 */
class Users extends Model
{
    /**
     * @Primary
     * @Identity
     * @Column(type="biginteger",nullable=false)
     */
    public $userID;

    /**
     * @Column(type="varchar",size=255,nullable=false)
     */
    public $emailAddress;

    /**
     * @Column(type="varchar",size=100,nullable=false)
     */
    public $password;
}
