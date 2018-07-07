<?php

namespace Sta\Semaphore;

use Sta\Semaphore\Adapter\Emulator;
use Sta\Semaphore\Adapter\Native;
use Sta\Semaphore\Adapter\SemaphoreAdapterInterface;

/**
 * Esta classe pode ser usada para obter acesso exclusivo a recursos na máquina local.
 * Quando disponível será usado as funções de semáfora nativas do PHP (http://www.php.net/manual/pt_BR/ref.sem.php).
 * Como estas funções não fazem parte da instalação padrão, a {@link \Sta\Semaphore\Semaphore } vai simular um semáforo
 * usando arquivos temporários para garantir o mesmo comportamento as funções nativas do PHP.
 * @author Stavarengo
 */
class Semaphore
{
    /**
     * Lista dos semáforos criados. Esta lista é usada para liberar os semáforos quando o script termina.
     * @see Semaphore::releaseAll()
     * @var array[]
     */
    private static $semaphores = [];
    /**
     * @var Semaphore
     */
    private static $instance;
    /**
     * @var SemaphoreAdapterInterface
     */
    private $_implementation = null;

    private function __construct()
    {
        register_shutdown_function([$this, 'releaseAll']);

        if (Native::hasNativeSuporte()) {
            $this->_implementation = Native::getInstance();
        } else {
            $this->_implementation = Emulator::getInstance();
        }
    }

    /**
     * @return Semaphore
     */
    public static function getInstance(): Semaphore
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Obtem acesso exclusivo.
     *
     * @param int $key
     *
     * @return array
     *        O semáforo criado. Este retorno deve ser usado liberar o semáforo com a função {@link Semaphore::release() }
     * @throws \Sta\Semaphore\Adapter\Exception\RuntimeException
     *        Se não conseguiu obter acesso exclusivo.
     */
    public static function acquire(int $key): array
    {
        $semaphore = self::getInstance()->_acquire($key);

        self::$semaphores[$key] = self::getInstance()->_createSemaphore($key, $semaphore);
        return self::$semaphores[$key];
    }

    /**
     * Libera o semáforo criado com {@link Semaphore::acquire() }.
     * Se o semafóro não existir nada é feito.
     *
     * @param array $semaphore
     *        O retorno da função {@link Semaphore::acquire() }
     *
     * @return void
     * @throws \Sta\Semaphore\Adapter\Exception\RuntimeException
     *        Se não conseguiur liberar o semáforo.
     */
    public static function release(array $semaphore): void
    {
        if (!isset($semaphore['key']) || !isset($semaphore['semaphore'])) {
            //O parâmetro recebido não está no formato válido.
            return;
        }

        self::getInstance()->_release($semaphore['semaphore']);

        $key = $semaphore['key'];
        if (isset(self::$semaphores[$key])) {
            unset(self::$semaphores[$key]);
        }
    }

    /**
     * Função invocada automaticamente quando o script é finalizado.
     * O registro desta função é feito com {@link register_shutdown_function() } no construtor desta classe.
     *
     * @see register_shutdown_function()
     */
    public function releaseAll()
    {
        foreach (self::$semaphores as $semaphore) {
            if ($semaphore) {
                self::release($semaphore);
            }
        }
    }

    /**
     * @see \Sta\Semaphore\Adapter\SemaphoreAdapterInterface::acquire()
     */
    private function _acquire(int $key)
    {
        return $this->_implementation->acquire($key);
    }

    /**
     * @see \Sta\Semaphore\Adapter\SemaphoreAdapterInterface::release()
     */
    private function _release($semaphore): void
    {
        $this->_implementation->release($semaphore);
    }

    /**
     *
     * @param int $key
     *
     * @param $semaphore
     *
     * @return array
     *        O retorno da função {@link Semaphore::acquire() }.
     */
    private function _createSemaphore(int $key, $semaphore): array
    {
        return ['key' => $key, 'semaphore' => $semaphore];
    }
}
