<?php
use \core\SGA;

function atendimentoInfo($atendimento) {
    ?>
    <div class="senha">
        <h3 class="title"><?php SGA::out(_('Atendimento')) ?></h3>
        <ul class="info <?php SGA::out(($atendimento && $atendimento->getSenha()->isPrioridade()) ? ' prioridade' : '') ?>">
            <li class="numero">
                <span class="label"><?php SGA::out(_('Senha')) ?></span>
                <span class="value"><?php SGA::out(($atendimento) ? $atendimento->getSenha()->toString() : '') ?></span>
            </li>
            <li class="servico">
                <span class="label"><?php SGA::out(_('Serviço')) ?></span>
                <span class="value"><?php SGA::out(($atendimento) ? $atendimento->getServicoUnidade()->getNome() : '') ?></span>
            </li>
            <li class="nome-prioridade">
                <span class="label"><?php SGA::out(_('Prioridade')) ?></span>
                <span class="value"><?php SGA::out(($atendimento) ? $atendimento->getSenha()->getPrioridade()->getNome() : '') ?></span>
            </li>
            <li class="nome">
                <span class="label"><?php SGA::out(_('Nome')) ?></span>
                <span class="value"><?php SGA::out(($atendimento) ? $atendimento->getCliente()->getNome() : '') ?></span>
            </li>
        </ul>
    </div>
    <?php
}

function btnControl($label, $action) {
    ?>
    <button class="btn-control <?php SGA::out($action) ?>" onclick="SGA.Atendimento.<?php SGA::out($action) ?>()" title="<?php SGA::out(_($label)) ?>"><?php SGA::out(_($label)) ?></button>
    <?php
}

$guiche = $context->getUser()->getGuiche();

?>
<div id="dialog-guiche" title="<?php SGA::out(_('Guichê')) ?>" style="display:none">
    <form id="guiche_form" action="<?php SGA::out(SGA::url('set_guiche')) ?>" method="post">
        <div>
            <label><?php SGA::out(_('Número')) ?></label>
            <input type="text" id="numero_guiche" name="guiche" maxlength="3" class="w50" value="<?php SGA::out($context->getCookie()->get('guiche')) ?>" />
        </div>
    </form>
    <script type="text/javascript">
        $('#guiche_form').on('submit', function() {
            var numero = parseInt($('#numero_guiche').val().trim());
            if (isNaN(numero) || numero <= 0) {
                $('#numero_guiche').val('');
                return false;
            }
            return true;
        });
    </script>
</div>
<?php

// se ainda nao definiu o guiche, exibe automaticamente a dialog
if ($guiche <= 0) {
    ?>
    <script type="text/javascript">SGA.Atendimento.updateGuiche("<?php SGA::out(_('Salvar')) ?>"); $('#guiche').focus();</script>
    <?php
} 
// guiche definido, exibe tela de atendimento
else {
    ?>
    <div id="atendimento">
        <div id="guiche">
            <span class="label"><?php SGA::out(_('Guichê')) ?></span>
            <span class="numero"><?php SGA::out($guiche) ?></span>
            <a href="javascript:void(0)" onclick="SGA.Atendimento.updateGuiche('<?php SGA::out(_('Salvar') )?>')"><?php SGA::out(_('Alterar')) ?></a>
        </div>
        <div id="controls">
            <div id="chamar" class="control" style="display:none">
                <?php btnControl('Chamar próximo', 'chamar') ?>
            </div>
            <div id="iniciar" class="control" style="display:none">
                <?php 
                    atendimentoInfo($atendimento);
                    btnControl('Chamar novamente', 'chamar');
                    btnControl('Iniciar atendimento', 'iniciar') ;
                    btnControl('Não compareceu', 'naocompareceu') ;
                ?>
            </div>
            <div id="encerrar" class="control" style="display:none">
                <?php 
                    atendimentoInfo($atendimento); 
                    btnControl('Encerrar atendimento', 'encerrar');
                    btnControl('Erro de triagem', 'errotriagem') ;
                ?>
            </div>
        </div>
        <div id="fila">
            <span><?php SGA::out(_('Minha fila')) ?>:</span>
            <ul></ul>
        </div>
    </div>
    <script type="text/javascript">
        <?php
            $status = ($atendimento) ? $atendimento->getStatus() : 1;
        ?>
        SGA.Atendimento.filaVazia = '<?php SGA::out(_('Fila Vazia')) ?>';
        SGA.Atendimento.marcarErroTriagem = '<?php SGA::out(_('Realmente deseja marcar como erro de triagem?')) ?>';
        SGA.Atendimento.marcarNaoCompareceu = '<?php SGA::out(_('Realmente deseja marcar como não compareceu?')) ?>';
        SGA.Atendimento.init(<?php SGA::out($status) ?>);
    </script>
    <?php
}