<?php

namespace Tests\Stores;

use Simplon\Mysql\CrudStore;
use Simplon\Mysql\CrudModelInterface;
use Simplon\Mysql\CreateQueryBuilder;
use Simplon\Mysql\ReadQueryBuilder;
use Simplon\Mysql\UpdateQueryBuilder;
use Simplon\Mysql\DeleteQueryBuilder;
use Tests\Models\User;

class UserStore extends CrudStore
{
    public function getTableName(): string
    {
        return 'users';
    }

    public function getModel(): CrudModelInterface
    {
        return new User();
    }

    public function create(CreateQueryBuilder $builder)
    {
        return $this->crudCreate($builder);
    }

    public function read(?ReadQueryBuilder $builder = null): ?array
    {
        if ($builder === null) {
            $builder = new ReadQueryBuilder();
        }
        return $this->crudRead($builder);
    }

    public function readOne(ReadQueryBuilder $builder)
    {
        return $this->crudReadOne($builder);
    }

    public function update(UpdateQueryBuilder $builder)
    {
        return $this->crudUpdate($builder);
    }

    public function delete(DeleteQueryBuilder $builder)
    {
        return $this->crudDelete($builder);
    }
}