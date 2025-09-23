<?php

namespace Simplon\Mysql;

interface CrudStoreInterface
{
    /**
     * @return string
     */
    public function getTableName(): string;

    /**
     * @return CrudModelInterface
     */
    public function getModel();

    public function create(CreateQueryBuilder $builder);

    public function read(?ReadQueryBuilder $builder = null): ?array;

    public function readOne(ReadQueryBuilder $builder);

    public function update(UpdateQueryBuilder $builder);

    public function delete(DeleteQueryBuilder $builder);
}
