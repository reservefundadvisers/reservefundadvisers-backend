<?php

namespace Simplon\Mysql;

abstract class CrudModel extends Data implements CrudModelInterface
{
    /**
     * @param array $data
     *
     * @return static
     */
    public function fromPost(array $data): static {
        return $this->fromArray($data, false);
    }

    /**
     * @return static
     */
    public function beforeSave(): static
    {
        return $this;
    }

    /**
     * @return static
     */
    public function beforeUpdate(): static
    {
        return $this;
    }
}