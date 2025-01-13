<?php

namespace App\Documentation\Models;

/**
 * @OA\Schema(
 *     title="User model",
 *     description="User model",
 *     @OA\Xml(
 *         name="User"
 *     )
 * )
 */
class User
{
    /**
     * @OA\Property(
     *     title="ID",
     *     description="ID",
     *     format="int64",
     *     example=1
     * )
     *
     * @var integer
     */
    public $id;

    /**
     * @OA\Property(
     *      title="givenName",
     *      description="User's given name",
     *      example="John"
     * )
     *
     * @var string
     */
    public $givenName;
}