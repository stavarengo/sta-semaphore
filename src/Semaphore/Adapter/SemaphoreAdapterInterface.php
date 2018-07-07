<?php

namespace Sta\Semaphore\Adapter;

use Sta\Semaphore\Adapter\Exception\RuntimeException;

/**
 * @author Stavarengo
 */
interface SemaphoreAdapterInterface
{

    /**
     * Obtem acesso exclusivo.
     *
     * @param int $key
     *
     * @return mixed
     *        O semáforo criado. Este retorno deve ser usado p/ liberar o semáforo com a função
     *        {@link SemaphoreAdapterInterface::release() }
     * @throws RuntimeException
     *        Se não conseguiu obter acesso exclusivo.
     */
    public function acquire(int $key);

    /**
     * Libera o semáforo criado com {@link SemaphoreAdapterInterface::acquire() }.
     * Se o semafóro não existir nada é feito.
     *
     * @param mixed $semaphore
     *        O retorno da função {@link SemaphoreAdapterInterface::acquire() }
     *
     * @return void
     * @throws RuntimeException
     *        Se não conseguiur liberar o semáforo.
     */
    public function release($semaphore): void;
}
