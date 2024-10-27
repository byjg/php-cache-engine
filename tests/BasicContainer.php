<?php

namespace Tests;

use Psr\Container\ContainerInterface;

class BasicContainer implements ContainerInterface
{


    /**
     * @inheritDoc
     */
    public function get(string $id)
    {
        if ($id == "test-key") {
            return "container-key";
        }

        return null;
    }

    /**
     * @inheritDoc
     */
    public function has(string $id): bool
    {
        if ($id == "test-key") {
            return true;
        } else {
            return false;
        }
    }
}