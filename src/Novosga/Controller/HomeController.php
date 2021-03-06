<?php
namespace Novosga\Controller;

use \Novosga\SGAContext;
use \Novosga\Business\AcessoBusiness;
use \Novosga\Controller\SGAController;
use \Novosga\Security;
use \Novosga\Http\AjaxResponse;

/**
 * HomeController
 * 
 * @author Rogerio Lino <rogeriolino@gmail.com>
 *
 */
class HomeController extends SGAController {
    
    public function index(SGAContext $context) {
    }
    
    public function unidade(SGAContext $context) {
        $response = new AjaxResponse();
        $id = (int) $context->request()->getParameter('unidade');
        try {
            if (!$context->request()->isPost()) {
                throw new \Exception(_('Somente via POST'));
            }
            $unidade = $context->database()->createEntityManager()->find("Novosga\Model\Unidade", $id);
            $context->getUser()->setUnidade($unidade);
            // atualizando a sessao
            $context->setUser($context->getUser());
            $response->success = true;
        } catch (\Exception $e) {
            $response->message = $e->getMessage();
        }
        $context->response()->jsonResponse($response);
    }
    
    public function perfil(SGAContext $context) {
        $usuario = $context->getUser();
        $salvo = false;
        // se editando
        if ($context->request()->isPost()) {
            // atualizando sessao
            $usuario->setNome($context->request()->getParameter('nome'));
            $usuario->setSobrenome($context->request()->getParameter('sobrenome'));
            $context->setUser($usuario);
            // atualizando banco
            $query = $context->database()->createEntityManager()->createQuery("
                UPDATE 
                    Novosga\Model\Usuario e 
                SET 
                    e.nome = :nome, e.sobrenome = :sobrenome 
                WHERE 
                    e.id = :id
            ");
            $query->setParameter('id', $usuario->getId());
            $query->setParameter('nome', $usuario->getNome());
            $query->setParameter('sobrenome', $usuario->getSobrenome());
            $query->execute();
            $salvo = true;
        }
        $this->app()->view()->set('salvo', $salvo);
        $this->app()->view()->set('usuario', $usuario);
    }
    
    public function alterar_senha(SGAContext $context) {
        $response = new AjaxResponse();
        $usuario = $context->getUser();
        try {
            if (!$usuario) {
                throw new \Exception(_('Nenhum usuário na sessão'));
            }
            $atual = $context->request()->getParameter('atual');
            $senha = $context->request()->getParameter('senha');
            $confirmacao = $context->request()->getParameter('confirmacao');
            $hash = $this->app()->getAcessoBusiness()->verificaSenha($senha, $confirmacao);
            $em = $context->database()->createEntityManager();
            // verificando senha atual
            $query = $em->createQuery("SELECT u.senha FROM Novosga\Model\Usuario u WHERE u.id = :id");
            $query->setParameter('id', $usuario->getId());
            $rs = $query->getSingleResult();
            if ($rs['senha'] != Security::passEncode($atual)) {
                throw new \Exception(_('Senha atual não confere'));
            }
            // atualizando o banco
            $query = $em->createQuery("UPDATE Novosga\Model\Usuario u SET u.senha = :senha WHERE u.id = :id");
            $query->setParameter('senha', $hash);
            $query->setParameter('id', $usuario->getId());
            $query->execute();
            $response->success = true;
        } catch (\Exception $e) {
            $response->message = $e->getMessage();
        }
        $context->response()->jsonResponse($response);
    }
    
    public function desativar_sessao(SGAContext $context) {
        $response = new AjaxResponse(true);
        $usuario = $context->getUser();
        $usuario->setAtivo(false);
        $context->setUser($usuario);
        $context->response()->jsonResponse($response);
    }
    
}
