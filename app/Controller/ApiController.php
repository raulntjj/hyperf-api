<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use Hyperf\Coroutine\WaitGroup;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

use function Hyperf\Coroutine\go;
use function Hyperf\Coroutine\defer;

#[Controller(prefix:"/api")]
class ApiController
{
    #[GetMapping(path:"teste")]
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        return $response->json([
            'message' => 'Hello Hyperf!',
        ]);
    }

    #[GetMapping(path: 'register-user')]
    public function registerUserSafe(RequestInterface $request, ResponseInterface $response)
    {
        $logs = [];
        
        go(function () use (&$logs) {
            // Defer é executado quando a corrotina termina
            defer(function () use (&$logs) {
                $logs[] = 'Cleanup: Fechando conexões de banco';
            });

            defer(function () use (&$logs) {
                $logs[] = 'Cleanup: Liberando memória cache';
            });

            $logs[] = 'Início: Validando dados do usuário';
            
            $user = new User([
                'name' => 'Carlos Mendes',
                'email' => 'carlos@example.com'
            ]);
            $user->save();
            
            $logs[] = 'Sucesso: Usuário registrado com ID ' . $user->id;
            
            // Simula processamento adicional (envio de email, etc)
            sleep(1);
            
            $logs[] = 'Finalizado: Email de boas-vindas enviado';
        });

        sleep(2);

        return $response->json([
            'message' => 'User registered with automatic cleanup',
            'execution_order' => $logs
        ]);
    }

    #[GetMapping(path: 'bulk-users')]
    public function bulkProcessUsers(RequestInterface $request, ResponseInterface $response)
    {
        $wg = new WaitGroup();
        $results = [];

        // Corrotina 1: Criar múltiplos usuários
        go(function () use ($wg, &$results) {
            $wg->add();
            
            Db::table('users')->truncate();
            
            $users = [
                ['name' => 'João Silva', 'email' => 'joao@example.com'],
                ['name' => 'Maria Santos', 'email' => 'maria@example.com'],
                ['name' => 'Pedro Oliveira', 'email' => 'pedro@example.com'],
            ];

            foreach ($users as $userData) {
                $user = new User($userData);
                $user->save();
            }
            
            $results['created'] = count($users);
            $wg->done();
        });

        // Corrotina 2: Buscar e atualizar usuário após um delay
        go(function () use ($wg, &$results) {
            $wg->add();
            sleep(1); // Simula uma operação demorada
            
            $user = User::where('email', 'maria@example.com')->first();
            if ($user) {
                $user->name = 'Maria Santos Updated';
                $user->save();
                $results['updated'] = $user->name;
            }
            
            $wg->done();
        });

        // Corrotina 3: Contar usuários após um delay
        go(function () use ($wg, &$results) {
            $wg->add();
            sleep(2); // Simula outra operação demorada
            
            $count = User::count();
            $results['total_users'] = $count;
            
            $wg->done();
        });

        // Aguarda todas as corrotinas finalizarem
        $wg->wait();

        return $response->json([
            'message' => 'Users processed in parallel successfully',
            'results' => $results
        ]);
    }
}
