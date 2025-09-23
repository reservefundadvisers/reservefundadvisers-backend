<?php

namespace Simplon\Mysql;
abstract class AbstractBuilder
{
    static function create() {
        return new static();
    }
}