<?php

/**
 * -------------------------------------------------------------------------
 * Plugin de Manutenção Preventiva para GLPI
 * -------------------------------------------------------------------------
 *
 * LICENÇA
 *
 * Este arquivo é parte do Plugin de Manutenção Preventiva.
 *
 * Manutenção Preventiva é um software livre; você pode redistribuí-lo e/ou modificar
 * sob os termos da Licença Pública Geral GNU conforme publicada pela
 * Free Software Foundation; ou versão 2 da Licença, ou
 * (a seu critério) qualquer versão posterior.
 * 
 * Manutenção Preventiva é distribuído na esperança de que seja útil,
 * mas SEM QUALQUER GARANTIA; sem mesmo a garantia implícita de
 * COMERCIALIZAÇÃO ou ADEQUAÇÃO A UM DETERMINADO FIM. Veja o
 * GNU General Public License para mais detalhes.
 *
 * Você deve ter recebido uma cópia da Licença Pública Geral GNU
 * junto com o Manutenção Preventiva. Se não, veja <http://www.gnu.org/licenses/>.
 * @copyright Copyright (C) 2026 GLPI Community
 * @license   GPLv2+
 * @link      https://example.com
 * -------------------------------------------------------------------------
 */

/**
 * -------------------------------------------------------------------------
 * Preventive Maintenance plugin for GLPI
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of Preventive Maintenance.
 *
 * Preventive Maintenance is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Preventive Maintenance is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Preventive Maintenance. If not, see <http://www.gnu.org/licenses/>.
 * @copyright Copyright (C) 2026 GLPI Community
 * @license   GPLv2+
 * @link      https://example.com
 * -------------------------------------------------------------------------
 */

// Inclui arquivos necessários do GLPI
include('../../../inc/includes.php');
global $DB;

// Verificação de segurança
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

// Verifica permissões
Session::checkRight('plugin_preventivemaintenance', READ);

// Instancia a classe principal
$pm = new PluginPreventivemaintenancePreventivemaintenance();

// Handles technician profiles selection if submitted via AJAX/POST
if (isset($_POST['save_selected_profiles'])) {
    $_SESSION['plugin_preventivemaintenance_selected_profiles'] = $_POST['profiles'] ?? ['Technician'];
    echo "ok";
    exit();
}

// Handles ticket resolution directly from the portal
if (isset($_POST['solve_maintenance_ticket'])) {
    // Check permissions
    if (!Session::haveRight('plugin_preventivemaintenance', UPDATE)) {
        echo json_encode(['success' => false, 'message' => __('Você não tem permissão para atualizar manutenções.')]);
        exit();
    }
    
    $ticket_id = (int)$_POST['ticket_id'];
    $status_choice = $_POST['status_choice']; // 'solved' or 'pending'
    $content = trim($_POST['content']);
    
    if ($ticket_id <= 0) {
        echo json_encode(['success' => false, 'message' => __('Chamado inválido.')]);
        exit();
    }
    
    if (empty($content)) {
        echo json_encode(['success' => false, 'message' => __('O preenchimento da descrição é obrigatório.')]);
        exit();
    }
    
    try {
        $ticket = new Ticket();
        if (!$ticket->getFromDB($ticket_id)) {
            echo json_encode(['success' => false, 'message' => __('Chamado não encontrado no GLPI.')]);
            exit();
        }
        
        if ($status_choice === 'solved') {
            // Add solution via ITILSolution to solve the ticket
            $solution = new ITILSolution();
            $solution_id = $solution->add([
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'content'  => htmlescape($content)
            ]);
            
            if ($solution_id) {
                // Run resolution explicitly to ensure db fields are updated immediately
                PluginPreventivemaintenancePreventivemaintenance::updateMaintenanceOnTicketResolution($ticket_id);
                
                echo json_encode(['success' => true, 'message' => __('Manutenção finalizada e chamado solucionado com sucesso!')]);
            } else {
                echo json_encode(['success' => false, 'message' => __('Falha ao adicionar a solução ao chamado no GLPI.')]);
            }
        } elseif ($status_choice === 'pending') {
            // Update ticket status to WAITING (Pending)
            $ticket->update([
                'id' => $ticket_id,
                'status' => Ticket::WAITING
            ]);
            
            // Add a follow-up recording the explanation
            $followup = new ITILFollowup();
            $followup_id = $followup->add([
                'itemtype' => 'Ticket',
                'items_id' => $ticket_id,
                'content'  => htmlescape("Manutenção Preventiva Pendente: " . $content)
            ]);
            
            if ($followup_id) {
                echo json_encode(['success' => true, 'message' => __('Chamado colocado em espera com sucesso!')]);
            } else {
                echo json_encode(['success' => false, 'message' => __('Falha ao registrar acompanhamento no chamado.')]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => __('Opção de status inválida.')]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    }
    exit();
}

// Wrapper functions calling class methods
function getPluginConfig($name) {
    return PluginPreventivemaintenancePreventivemaintenance::getPluginConfig($name);
}

function updatePluginConfig($name, $value) {
    return PluginPreventivemaintenancePreventivemaintenance::updatePluginConfig($name, $value);
}

function hasOpenMaintenanceTicket($computer_id, $maintenance_name) {
    return PluginPreventivemaintenancePreventivemaintenance::hasOpenMaintenanceTicket($computer_id, $maintenance_name);
}

function registerMaintenanceTicket($ticket_id, $computer_id, $maintenance_name) {
    return PluginPreventivemaintenancePreventivemaintenance::registerMaintenanceTicket($ticket_id, $computer_id, $maintenance_name);
}

function updateMaintenanceOnTicketResolution($ticket_id) {
    return PluginPreventivemaintenancePreventivemaintenance::updateMaintenanceOnTicketResolution($ticket_id);
}

function cleanResolvedMaintenanceTickets() {
    return PluginPreventivemaintenancePreventivemaintenance::cleanResolvedMaintenanceTickets();
}

function createMaintenanceTicket($computer_id, $maintenance_name, $technician_id) {
    return PluginPreventivemaintenancePreventivemaintenance::createMaintenanceTicket($computer_id, $maintenance_name, $technician_id);
}

function getTicketStatusBadge($status_id, $status_label) {
    $badge_class = 'bg-secondary-lt';
    switch ($status_id) {
        case Ticket::INCOMING: // Novo
            $badge_class = 'bg-blue-lt';
            break;
        case Ticket::ASSIGNED: // Atribuído
            $badge_class = 'bg-azure-lt';
            break;
        case Ticket::PLANNED: // Planejado
            $badge_class = 'bg-purple-lt';
            break;
        case Ticket::WAITING: // Pendente
            $badge_class = 'bg-red-lt';
            break;
        case Ticket::SOLVED: // Solucionado
            $badge_class = 'bg-green-lt';
            break;
        case Ticket::CLOSED: // Fechado
            $badge_class = 'bg-secondary-lt';
            break;
    }
    return "<span class='badge $badge_class' style='font-size: 0.85rem; padding: 4px 8px;'>$status_label</span>";
}

// Fetch technician profiles variables
$profile = new Profile();
$all_profiles = $profile->find([], 'name ASC');
$selected_profiles = $_SESSION['plugin_preventivemaintenance_selected_profiles'] ?? ['Technician'];

$technicians = [];
$user = new User();
$profile_user = new Profile_User();

foreach ($selected_profiles as $profile_name) {
    $technician_profile = $profile->find(['name' => $profile_name]);
    if (!empty($technician_profile)) {
        $technician_profile_id = key($technician_profile);
        $profile_users = $profile_user->find(['profiles_id' => $technician_profile_id]);
        
        foreach ($profile_users as $pu) {
            $user->getFromDB($pu['users_id']);
            if ($user->fields['is_active'] && !isset($technicians[$user->getID()])) {
                $technicians[$user->getID()] = $user->getName();
            }
        }
    }
}

// Fetch entity and computer variables for the add tab form
$entity = new Entity();
$entities = $entity->find(['id' => $_SESSION['glpiactiveentities']], 'completename ASC');

// Fetch all entity parent-child relationships for recursive assets check
$parent_entities_map = [];
foreach ($entity->find() as $ent) {
    $parent_entities_map[(int)$ent['id']] = (int)$ent['entities_id'];
}

$computer = new Computer();
$all_computers = $computer->find(['is_deleted' => 0], "name ASC");

$existing_maintenances = $pm->find(['itemtype' => 'Computer']);
$blocked_computers = [];
foreach ($existing_maintenances as $maintenance) {
    $blocked_computers[] = (int)$maintenance['items_id'];
}

// Obtém configuração do Auto Ticket
$auto_ticket = getPluginConfig('auto_ticket');
if ($auto_ticket === false) {
    $auto_ticket = '0';
    updatePluginConfig('auto_ticket', $auto_ticket);
}
$auto_ticket_enabled = ($auto_ticket === '1');

// Inicializa filtros
$filters = [
    'status' => 'all',
    'date_from' => '',
    'date_to' => '',
    'technician' => 0,
    'entity' => 0
];

// Atualiza filtros da requisição
if (isset($_GET['status'])) {
    $filters['status'] = $_GET['status'];
}
if (isset($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}
if (isset($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}
if (isset($_GET['technician'])) {
    $filters['technician'] = (int)$_GET['technician'];
}
if (isset($_GET['entity'])) {
    $filters['entity'] = (int)$_GET['entity'];
}

// Processa exclusão
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    if ($id > 0 && $pm->canDelete()) {
        if ($pm->delete(['id' => $id])) {
            Session::addMessageAfterRedirect(
                __('Registro apagado com sucesso!'),
                true,
                INFO
            );
        } else {
            Session::addMessageAfterRedirect(
                __('Falha ao apagar registro!'),
                false,
                ERROR
            );
        }
        Html::redirect('preventivemaintenance.php');
    }
}

// Processa toggle do Auto Ticket
if (isset($_GET['toggle_auto_ticket'])) {
    $new_value = $auto_ticket_enabled ? '0' : '1';
    if (updatePluginConfig('auto_ticket', $new_value)) {
        $auto_ticket_enabled = !$auto_ticket_enabled;
        Session::addMessageAfterRedirect(
            $auto_ticket_enabled ? __('Auto ticket ativado com sucesso!') : __('Auto ticket desativado com sucesso!'),
            true,
            INFO
        );
    } else {
        Session::addMessageAfterRedirect(
            __('Falha ao atualizar a configuração do auto ticket!'),
            false,
            ERROR
        );
    }
    Html::redirect('preventivemaintenance.php');
}

// Limpa tickets resolvidos
cleanResolvedMaintenanceTickets();

// Cria tickets automáticos se habilitado
if ($auto_ticket_enabled) {
    $all_maintenances = $pm->find([], 'next_maintenance_date ASC');
    
    foreach ($all_maintenances as $item) {
        if (!empty($item['last_maintenance_date']) && !empty($item['next_maintenance_date'])) {
            $last = strtotime($item['last_maintenance_date']);
            $next = strtotime($item['next_maintenance_date']);
            $now = time();
            
            $total_days = $next - $last;
            $elapsed_days = $now - $last;
            
            if ($total_days > 0) {
                $percent = min(100, max(0, round(($elapsed_days / $total_days) * 100)));
                
                if ($percent >= 99) {
                    $ticket_id = createMaintenanceTicket(
                        $item['items_id'],
                        $item['name'],
                        $item['technician_id']
                    );
                    
                    if ($ticket_id) {
                        Session::addMessageAfterRedirect(
                            sprintf(__('Ticket criado automaticamente para manutenção preventiva: %s'), $item['name']),
                            true,
                            INFO
                        );
                    }
                }
            }
        }
    }
}

// Prepara critérios de busca
$criteria = [];
if (!empty($filters['technician'])) {
    $criteria['technician_id'] = (int)$filters['technician'];
}
if (!empty($filters['entity'])) {
    $criteria['entities_id'] = (int)$filters['entity'];
}

// Adiciona filtros de data
if (!empty($filters['date_from']) || !empty($filters['date_to'])) {
    $date_criteria = [];
    if (!empty($filters['date_from'])) {
        $date_criteria[] = ['next_maintenance_date' => ['>=', $filters['date_from']]];
    }
    if (!empty($filters['date_to'])) {
        $date_criteria[] = ['next_maintenance_date' => ['<=', $filters['date_to']]];
    }
    $criteria[] = ['AND' => $date_criteria];
}

// Busca itens
$all_items = $pm->find($criteria, 'entities_id, next_maintenance_date ASC');

// Obtém lista de técnicos cadastrados nas manutenções
$technicians_in_maintenance = [];
foreach ($all_items as $item) {
    if ($item['technician_id'] > 0) {
        $technicians_in_maintenance[$item['technician_id']] = $item['technician_id'];
    }
}

// Carrega os dados dos técnicos (com nome completo - realname)
$technicians_data = [];
if (!empty($technicians_in_maintenance)) {
    $user = new User();
    $technicians_iterator = $user->find(['id' => array_values($technicians_in_maintenance)]);
    foreach ($technicians_iterator as $tech) {
        $technicians_data[$tech['id']] = formatUserName($tech['id'], $tech['name'], $tech['realname'], $tech['firstname']);
    }
}

// Função para obter caminho da entidade
function getFullEntityPath($entity_id) {
    $entity = new Entity();
    if ($entity->getFromDB($entity_id)) {
        $path = $entity->getName();
        $parent_id = $entity->getField('entities_id');
        
        while ($parent_id > 0) {
            $parent = new Entity();
            if ($parent->getFromDB($parent_id)) {
                $path = $parent->getName().' > '.$path;
                $parent_id = $parent->getField('entities_id');
            } else {
                break;
            }
        }
        return $path;
    }
    return '';
}

// Agrupa itens por entidade
$items_by_entity = [];
foreach ($all_items as $item) {
    $entity_id = $item['entities_id'];
    $entity_path = getFullEntityPath($entity_id);
    
    if ($entity_path !== '') {
        if (!isset($items_by_entity[$entity_path])) {
            $items_by_entity[$entity_path] = [
                'name' => $entity_path,
                'items' => []
            ];
        }
        
        $last = !empty($item['last_maintenance_date']) ? strtotime($item['last_maintenance_date']) : 0;
        $next = !empty($item['next_maintenance_date']) ? strtotime($item['next_maintenance_date']) : 0;
        $now = time();
        
        $include_item = true;
        
        if ($filters['status'] !== 'all' && $next > 0 && $last > 0) {
            $total_days = $next - $last;
            $elapsed_days = $now - $last;
            
            $percent = ($total_days > 0) ? min(100, max(0, round(($elapsed_days / $total_days) * 100))) : 0;
            
            switch ($filters['status']) {
                case 'ontime':
                    if ($percent >= 80) $include_item = false;
                    break;
                case 'warning':
                    if ($percent < 80 || $percent >= 98) $include_item = false;
                    break;
                case 'urgent':
                    if ($percent < 98) $include_item = false;
                    break;
                case 'undefined':
                    if ($next > 0 && $last > 0) $include_item = false;
                    break;
            }
        }
        
        if ($include_item) {
            $items_by_entity[$entity_path]['items'][] = $item;
        }
    }
}

// Ordena entidades
uksort($items_by_entity, function($a, $b) {
    return strnatcasecmp($a, $b);
});

// Exibe cabeçalho
Html::header(
    __('Preventive Maintenance', 'preventivemaintenance'),
    $_SERVER['PHP_SELF'],
    'tools',
    'preventivemaintenance'
);
?>

<style>
    /* Estilos para o datepicker com intervalo */
    .ui-datepicker {
        width: 350px !important;
        padding: 10px;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        font-family: Arial, sans-serif;
    }
    .ui-datepicker-header {
        background: #f8f9fa;
        border-radius: 6px 6px 0 0;
        padding: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .ui-datepicker-title {
        font-weight: bold;
        display: flex;
        gap: 10px;
    }
    .ui-datepicker-month, .ui-datepicker-year {
        padding: 3px 5px;
        border-radius: 3px;
        border: 1px solid #ced4da;
    }
    .ui-datepicker-prev, .ui-datepicker-next {
        position: relative;
        top: auto;
        left: auto;
        right: auto;
        cursor: pointer;
        padding: 3px 8px;
        border-radius: 3px;
        background: #f0f0f0;
    }
    .ui-datepicker-prev:hover, .ui-datepicker-next:hover {
        background: #e0e0e0;
    }
    .ui-datepicker-calendar {
        width: 100%;
        margin-top: 10px;
    }
    .ui-datepicker-interval {
        padding: 10px;
        background: #f5f5f5;
        border-bottom: 1px solid #ddd;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-items: center;
        margin: -10px -10px 10px -10px;
        border-radius: 8px 8px 0 0;
    }
    .ui-datepicker-interval select {
        padding: 6px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        background: white;
        flex-grow: 1;
    }
    .ui-datepicker-interval button {
        padding: 6px 12px;
        background: #4CAF50;
        color: white;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        flex-grow: 1;
    }
    .ui-datepicker-interval button:hover {
        background: #45a049;
    }
</style>

<?php
$current_tab = $_GET['tab'] ?? 'portal';
?>

<div class="card card-tabs mb-4 mx-auto" style="max-width: 98%;">
    <div class="card-header p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 border-bottom-0" role="tablist">
            <li class="nav-item" role="presentation">
                <a href="preventivemaintenance.php?tab=portal" class="nav-link <?= $current_tab === 'portal' ? 'active' : '' ?>" style="padding: 1rem 1.5rem;">
                    <i class="fas fa-chart-line me-2"></i> <?= __('Painel') ?>
                </a>
            </li>
            <?php if ($pm->canCreate()): ?>
                <li class="nav-item" role="presentation">
                    <a href="preventivemaintenance.php?tab=add" class="nav-link <?= $current_tab === 'add' ? 'active' : '' ?>" style="padding: 1rem 1.5rem;">
                        <i class="fas fa-plus me-2"></i> <?= __('Nova Manutenção') ?>
                    </a>
                </li>
            <?php endif; ?>
            <li class="nav-item" role="presentation">
                <a href="preventivemaintenance.php?tab=history" class="nav-link <?= $current_tab === 'history' ? 'active' : '' ?>" style="padding: 1rem 1.5rem;">
                    <i class="fas fa-history me-2"></i> <?= __('Chamados Gerados') ?>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a href="preventivemaintenance.php?tab=config" class="nav-link <?= $current_tab === 'config' ? 'active' : '' ?>" style="padding: 1rem 1.5rem;">
                    <i class="fas fa-cog me-2"></i> <?= __('Configurações') ?>
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body">

    <!-- ==================== ABA 1: PAINEL ==================== -->
    <?php if ($current_tab === 'portal'): ?>
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3 class="card-title m-0"><i class="fas fa-chart-bar me-2"></i><?= __('Painel de Manutenções') ?></h3>
            <button id="toggleFilters" class="btn btn-outline-secondary">
                <i class="fas fa-filter me-2"></i><?= __('Filters') ?>
            </button>
        </div>

        <div id="advancedFilters" class="card mb-4 border" style="<?= isset($_GET['filter_applied']) ? '' : 'display: none;' ?> background-color: var(--tblr-bg-surface-secondary, #f8f9fa);">
            <div class="card-body">
                <form method="get" action="">
                    <input type="hidden" name="filter_applied" value="1">
                    <input type="hidden" name="tab" value="portal">
                    
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted"><?= __('Status') ?></label>
                            <select name="status" class="form-select">
                                <option value="all" <?= $filters['status'] === 'all' ? 'selected' : '' ?>><?= __('Todos') ?></option>
                                <option value="ontime" <?= $filters['status'] === 'ontime' ? 'selected' : '' ?>><?= __('Em dia') ?></option>
                                <option value="warning" <?= $filters['status'] === 'warning' ? 'selected' : '' ?>><?= __('Atenção') ?></option>
                                <option value="urgent" <?= $filters['status'] === 'urgent' ? 'selected' : '' ?>><?= __('Urgente') ?></option>
                                <option value="undefined" <?= $filters['status'] === 'undefined' ? 'selected' : '' ?>><?= __('Indefinido') ?></option>
                            </select>
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted"><?= __('De') ?></label>
                            <input type="date" name="date_from" class="form-control" value="<?= $filters['date_from'] ?>">
                        </div>
                        
                        <div class="col-md-2">
                            <label class="form-label fw-bold small text-muted"><?= __('Até') ?></label>
                            <input type="date" name="date_to" class="form-control" value="<?= $filters['date_to'] ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted"><?= __('Technician') ?></label>
                            <select name="technician" class="form-select">
                                <option value="0"><?= __('Todos') ?></option>
                                <?php
                                if (!empty($technicians_data)) {
                                    foreach ($technicians_data as $tech_id => $tech_name) {
                                        $selected = ($filters['technician'] == $tech_id) ? 'selected' : '';
                                        echo "<option value='$tech_id' $selected>$tech_name</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label class="form-label fw-bold small text-muted"><?= __('Entity') ?></label>
                            <?php 
                            $entity_options = [
                                'name' => 'entity',
                                'value' => $filters['entity'],
                                'display' => false,
                                'width' => '100%'
                            ];
                            echo Entity::dropdown($entity_options);
                            ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 mt-3">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-filter me-2"></i><?= __('Aplicar') ?>
                        </button>
                        <a href="preventivemaintenance.php?tab=portal" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-times me-2"></i><?= __('Limpar') ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php if (empty($items_by_entity)): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i><?= __('Nenhum registro encontrado.') ?>
            </div>
        <?php else: ?>
            <?php foreach ($items_by_entity as $entity): ?>
                <?php if (empty($entity['items'])) continue; ?>
                
                <div class="card mb-4 border shadow-sm">
                    <div class="card-header bg-body-tertiary py-3 px-4">
                        <h4 class="card-title m-0"><i class="fas fa-building me-2 text-muted"></i><?= $entity['name'] ?></h4>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover table-striped align-middle card-table mb-0">
                            <thead>
                                <tr>
                                    <th style="text-align: center"><?= __('ID') ?></th>
                                    <th style="text-align: center"><?= __('Nome/descr.') ?></th>
                                    <th style="text-align: center"><?= __('Computer') ?></th>
                                    <th style="text-align: center"><?= __('Technician') ?></th>
                                    <th style="text-align: center"><?= __('Ult. Man.') ?></th>
                                    <th style="text-align: center"><?= __('Prox. Man') ?></th>
                                    <th style="text-align: center"><?= __('Status') ?></th>
                                    <th style="text-align: center"><?= __('Chamado (ID)') ?></th>
                                    <th style="text-align: center"><?= __('Status do Chamado') ?></th>
                                    <th style="text-align: center"><?= __('Actions') ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($entity['items'] as $item): ?>
                                    <?php
                                    $computer = new Computer();
                                    $computer_name = $computer->getFromDB($item['items_id']) ? $computer->getName() : __('N/A');
                                    
                                    $technician_name = isset($technicians_data[$item['technician_id']]) ? $technicians_data[$item['technician_id']] : '-';
                                    
                                    $last = !empty($item['last_maintenance_date']) ? strtotime($item['last_maintenance_date']) : 0;
                                    $next = !empty($item['next_maintenance_date']) ? strtotime($item['next_maintenance_date']) : 0;
                                    $now = time();
                                    
                                    // Verify if there is an active ticket generated for this computer and maintenance
                                    $active_ticket_id = 0;
                                    $active_ticket_status = 0;
                                    $ticket_criteria = [
                                        'SELECT' => [
                                            'glpi_plugin_preventivemaintenance_tickets.ticket_id',
                                            'glpi_tickets.status'
                                        ],
                                        'FROM' => 'glpi_plugin_preventivemaintenance_tickets',
                                        'INNER JOIN' => [
                                            'glpi_tickets' => [
                                                'ON' => [
                                                    'glpi_plugin_preventivemaintenance_tickets' => 'ticket_id',
                                                    'glpi_tickets' => 'id'
                                                ]
                                            ]
                                        ],
                                        'WHERE' => [
                                            'glpi_plugin_preventivemaintenance_tickets.computer_id' => (int)$item['items_id'],
                                            'glpi_plugin_preventivemaintenance_tickets.maintenance_name' => $item['name'],
                                            ['NOT' => ['glpi_tickets.status' => [Ticket::CLOSED, Ticket::SOLVED]]]
                                        ],
                                        'LIMIT' => 1
                                    ];
                                    $ticket_iterator = $DB->request($ticket_criteria);
                                    if (count($ticket_iterator)) {
                                        $ticket_row = $ticket_iterator->current();
                                        $active_ticket_id = (int)$ticket_row['ticket_id'];
                                        $active_ticket_status = (int)$ticket_row['status'];
                                    }
                                    
                                    $status_html = "<span class='badge bg-secondary-lt'>".__('Undefined')."</span>";
                                    
                                    if ($next > 0 && $last > 0) {
                                        $total_days = $next - $last;
                                        $elapsed_days = $now - $last;
                                        
                                        $percent = ($total_days > 0) ? min(100, max(0, round(($elapsed_days / $total_days) * 100))) : 0;
                                        
                                        if ($percent < 80) {
                                            $status_class = 'bg-success';
                                            $status_text = __('Em dia');
                                        } elseif ($percent >= 80 && $percent < 98) {
                                            $status_class = 'bg-warning text-dark';
                                            $status_text = __('Atenção');
                                        } else {
                                            $status_class = 'bg-danger';
                                            $status_text = __('Urgente');
                                        }
                                        
                                        $status_html = "<div class='progress' style='height: 1.5rem;'>
                                            <div class='progress-bar $status_class text-white fw-bold' role='progressbar' style='width: $percent%' aria-valuenow='$percent' aria-valuemin='0' aria-valuemax='100'>
                                                $percent% - $status_text
                                            </div>
                                        </div>";
                                    }
                                    ?>
                                    <tr>
                                         <td style="text-align: center"><?= $item['id'] ?></td>
                                         <td style="text-align: center"><?= $item['name'] ?></td>
                                         <td style="text-align: center"><?= $computer_name ?></td>
                                         <td style="text-align: center"><?= $technician_name ?></td>
                                         <td style="text-align: center"><?= !empty($item['last_maintenance_date']) ? Html::convDate($item['last_maintenance_date']) : '-' ?></td>
                                         <td style="text-align: center"><?= !empty($item['next_maintenance_date']) ? Html::convDate($item['next_maintenance_date']) : '-' ?></td>
                                         <td style="text-align: center"><?= $status_html ?></td>
                                         <td style="text-align: center">
                                             <?php if ($active_ticket_id > 0): ?>
                                                 <a href="/front/ticket.form.php?id=<?= $active_ticket_id ?>" target="_blank" style="font-weight: 600;">
                                                     #<?= $active_ticket_id ?>
                                                 </a>
                                             <?php else: ?>
                                                 -
                                             <?php endif; ?>
                                         </td>
                                         <td style="text-align: center">
                                             <?php if ($active_ticket_id > 0): ?>
                                                 <?= getTicketStatusBadge($active_ticket_status, Ticket::getStatus($active_ticket_status)) ?>
                                             <?php else: ?>
                                                 -
                                             <?php endif; ?>
                                         </td>
                                         <td style="text-align: center">
                                             <div class="action-buttons d-flex gap-1 justify-content-center">
                                                 <?php if ($active_ticket_id > 0 && Session::haveRight('plugin_preventivemaintenance', UPDATE)): ?>
                                                     <button type="button" 
                                                             class="btn btn-sm btn-icon btn-ghost-success btn-finish-maintenance" 
                                                             data-ticket-id="<?= $active_ticket_id ?>" 
                                                             data-computer-name="<?= htmlspecialchars($computer_name) ?>" 
                                                             data-maintenance-name="<?= htmlspecialchars($item['name']) ?>"
                                                             title="<?= __('Finalizar Manutenção') ?>">
                                                         <i class="ti ti-clipboard-check fs-2"></i>
                                                     </button>
                                                 <?php endif; ?>
                                                 <?php if (Session::haveRight('plugin_preventivemaintenance', UPDATE)): ?>
                                                     <a href="preventivemaintenance.form.php?id=<?= $item['id'] ?>" class="btn btn-sm btn-icon btn-ghost-warning" title="<?= __('Edit') ?>">
                                                         <i class="ti ti-edit fs-2"></i>
                                                     </a>
                                                 <?php endif; ?>
                                                 <?php if ($pm->canDelete()): ?>
                                                     <a href="preventivemaintenance.php?delete=<?= $item['id'] ?>" 
                                                        class="btn btn-sm btn-icon btn-ghost-danger"
                                                        title="<?= __('Delete') ?>"
                                                        onclick="return confirm('<?= __('Do you really want to delete this record?') ?>');">
                                                         <i class="ti ti-trash fs-2"></i>
                                                     </a>
                                                 <?php endif; ?>
                                             </div>
                                         </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ==================== ABA 2: CADASTRO ==================== -->
    <?php if ($current_tab === 'add' && $pm->canCreate()): ?>
        <div class="card border shadow-sm">
            <div class="card-header bg-body-tertiary">
                <h4 class="card-title m-0"><i class="fas fa-plus me-2"></i><?= __('Nova Manutenção') ?></h4>
            </div>
            <div class="card-body">
                <form method="post" action="preventivemaintenance.form.php" id="preventive_maintenance_form">
                    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                    <input type="hidden" name="add" value="1">
                    
                    <div id="step1">
                        <div class="mb-3">
                            <label for="entities_id_select" class="form-label fw-bold"><?php echo __('Entidade'); ?> <span class="text-danger">*</span></label>
                            <select name="entities_id_select" id="entities_id_select" class="form-select" required>
                                <option value=""><?php echo __('Selecione uma entidade'); ?></option>
                                <?php foreach ($entities as $ent) {
                                    echo "<option value='{$ent['id']}'>{$ent['completename']}</option>";
                                } ?>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary" id="nextButton">
                                <i class="fas fa-arrow-right me-2"></i><?php echo __('Próximo'); ?>
                            </button>
                        </div>
                    </div>
                    
                    <div id="step2" style="display: none;">
                        <input type="hidden" name="entities_id" id="entities_id" value="">
                        
                        <div class="alert alert-info py-2 px-3 mb-4">
                            <i class="fas fa-building me-2"></i><strong><?php echo __('Entidade selecionada:'); ?></strong>
                            <span id="selected-entity-name" class="fw-bold text-primary"></span>
                            (ID: <span id="selected-entity-id"></span>)
                        </div>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label fw-bold"><?php echo __('Nome da Manutenção'); ?> <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="technician_id" class="form-label fw-bold"><?php echo __('Técnico Responsável'); ?> <span class="text-danger">*</span></label>
                            <select name="technician_id" id="technician_id" class="form-select" required>
                                <option value=""><?php echo __('Selecione um técnico responsável'); ?></option>
                                <?php 
                                foreach ($technicians as $id => $name) {
                                    echo "<option value='{$id}'>{$name}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="items_id" class="form-label fw-bold"><?php echo __('Computador'); ?> <span class="text-danger">*</span></label>
                            <select name="items_id" id="items_id" class="form-select" required>
                                <option value=""><?php echo __('Selecione um computador'); ?></option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="last_maintenance_date" class="form-label fw-bold"><?php echo __('Última Manutenção'); ?></label>
                            <input type="text" id="last_maintenance_date" name="last_maintenance_date" class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="next_maintenance_date" class="form-label fw-bold"><?php echo __('Próxima Manutenção'); ?> <span class="text-danger">*</span></label>
                            <input type="text" id="next_maintenance_date" name="next_maintenance_date" class="form-control interval-field" required>
                        </div>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-secondary" id="backButton">
                                <i class="fas fa-arrow-left me-2"></i><?php echo __('Voltar'); ?>
                            </button>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save me-2"></i><?php echo __('Salvar'); ?>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- ==================== ABA 3: HISTÓRICO DE TICKETS ==================== -->
    <?php if ($current_tab === 'history'): ?>
        <?php
        $criteria_tickets = [
            'SELECT' => [
                'glpi_plugin_preventivemaintenance_tickets.id',
                'glpi_plugin_preventivemaintenance_tickets.ticket_id',
                'glpi_plugin_preventivemaintenance_tickets.computer_id',
                'glpi_plugin_preventivemaintenance_tickets.maintenance_name',
                'glpi_plugin_preventivemaintenance_tickets.date_creation',
                'glpi_tickets.status'
            ],
            'FROM' => 'glpi_plugin_preventivemaintenance_tickets',
            'LEFT JOIN' => [
                'glpi_tickets' => [
                    'ON' => [
                        'glpi_plugin_preventivemaintenance_tickets' => 'ticket_id',
                        'glpi_tickets' => 'id'
                    ]
                ]
            ],
            'ORDERBY' => 'date_creation DESC'
        ];
        $tickets_iterator = $DB->request($criteria_tickets);
        ?>

        <div class="d-flex align-items-center mb-3">
            <h3 class="card-title m-0"><i class="fas fa-history me-2"></i><?= __('Histórico de Chamados Gerados') ?></h3>
        </div>

        <?php if (count($tickets_iterator) === 0): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i><?= __('Nenhum chamado gerado automaticamente ainda.') ?>
            </div>
        <?php else: ?>
            <div class="table-responsive border rounded">
                <table class="table table-hover table-striped align-middle card-table mb-0">
                    <thead>
                        <tr>
                            <th style="text-align: center"><?= __('Chamado (ID)') ?></th>
                            <th style="text-align: center"><?= __('Manutenção Programada') ?></th>
                            <th style="text-align: center"><?= __('Ativo (Computador)') ?></th>
                            <th style="text-align: center"><?= __('Data de Geração') ?></th>
                            <th style="text-align: center"><?= __('Status do Ticket') ?></th>
                            <th style="text-align: center"><?= __('Ações') ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets_iterator as $t): ?>
                            <?php
                            $comp_obj = new Computer();
                            $comp_name = $comp_obj->getFromDB($t['computer_id']) ? $comp_obj->getName() : __('N/A');
                            
                            $status_id = $t['status'];
                            $status_label = Ticket::getStatus($status_id);
                            ?>
                            <tr>
                                <td style="text-align: center; font-weight: 600;">
                                    <a href="/front/ticket.form.php?id=<?= $t['ticket_id'] ?>" target="_blank">
                                        #<?= $t['ticket_id'] ?>
                                    </a>
                                </td>
                                <td style="text-align: center"><?= htmlspecialchars($t['maintenance_name']) ?></td>
                                <td style="text-align: center"><?= htmlspecialchars($comp_name) ?></td>
                                <td style="text-align: center"><?= Html::convDateTime($t['date_creation']) ?></td>
                                <td style="text-align: center;">
                                    <?= getTicketStatusBadge($status_id, $status_label) ?>
                                </td>
                                <td style="text-align: center;">
                                     <?php if (Session::haveRight('plugin_preventivemaintenance', UPDATE) && !in_array($status_id, [Ticket::CLOSED, Ticket::SOLVED])): ?>
                                         <button type="button" 
                                                 class="btn btn-sm btn-icon btn-ghost-success btn-finish-maintenance" 
                                                 data-ticket-id="<?= $t['ticket_id'] ?>" 
                                                 data-computer-name="<?= htmlspecialchars($comp_name) ?>" 
                                                 data-maintenance-name="<?= htmlspecialchars($t['maintenance_name']) ?>"
                                                 title="<?= __('Finalizar Manutenção') ?>">
                                             <i class="ti ti-clipboard-check fs-2"></i>
                                         </button>
                                     <?php else: ?>
                                         -
                                     <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- ==================== ABA 4: CONFIGURAÇÕES ==================== -->
    <?php if ($current_tab === 'config'): ?>
        <!-- Auto Ticket Card -->
        <div class="card mb-4 border shadow-sm">
            <div class="card-header bg-body-tertiary">
                <h4 class="card-title m-0"><i class="fas fa-magic me-2"></i><?= __('Criação Automática de Tickets') ?></h4>
            </div>
            <div class="card-body">
                <p class="text-muted"><?= __('Quando habilitado, o sistema cria chamados automaticamente no GLPI assim que a manutenção atinge ou ultrapassa a data programada (limiar de 99% do período).') ?></p>
                <div class="d-flex align-items-center mt-3">
                    <span class="h5 m-0 me-3"><?= __('Status do Auto Ticket:') ?></span>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" id="autoTicketSwitch" <?= $auto_ticket_enabled ? 'checked' : '' ?>
                               onclick="window.location.href='preventivemaintenance.php?toggle_auto_ticket=1&tab=config'" style="cursor: pointer; width: 2.5rem; height: 1.25rem;">
                        <label class="form-check-label fw-bold ms-2 <?= $auto_ticket_enabled ? 'text-success' : 'text-danger' ?>" for="autoTicketSwitch" style="cursor: pointer;">
                            <?= $auto_ticket_enabled ? __('Ativado') : __('Desativado') ?>
                        </label>
                    </div>
                </div>
            </div>
        </div>

        <!-- Perfis Técnicos Selection Card -->
        <div class="card border shadow-sm">
            <div class="card-header bg-body-tertiary">
                <h4 class="card-title m-0"><i class="fas fa-user-cog me-2"></i><?php echo __('Selecionar Perfis Técnicos'); ?></h4>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php echo __('Selecione quais perfis de usuários no GLPI serão listados como Técnicos Responsáveis no formulário de agendamento.'); ?></p>
                
                <form method="post" id="profileSelectionForm" action="preventivemaintenance.php?tab=config">
                    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                    <input type="hidden" name="save_selected_profiles" value="1">
                    
                    <div class="border rounded p-3 mb-3" style="max-height: 300px; overflow-y: auto; background-color: var(--tblr-bg-surface, #ffffff);">
                        <div class="row g-2">
                            <?php foreach ($all_profiles as $prof): ?>
                                <div class="col-md-4 col-sm-6">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="profiles[]" id="prof_<?= $prof['id'] ?>" value="<?php echo $prof['name']; ?>"
                                            <?php echo in_array($prof['name'], $selected_profiles) ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="prof_<?= $prof['id'] ?>" style="cursor: pointer;"><?php echo $prof['name']; ?></label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i><?php echo __('Salvar Seleção de Perfis'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Modal de Finalização de Manutenção Preventiva (Bootstrap 5) -->
    <div id="finishMaintenanceModal" class="modal fade" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check-double me-2 text-success"></i><?= __('Finalizar Manutenção Preventiva') ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="finishMaintenanceForm">
                    <?php echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]); ?>
                    <input type="hidden" name="solve_maintenance_ticket" value="1">
                    <input type="hidden" name="ticket_id" id="modal_ticket_id" value="">
                    
                    <div class="modal-body p-4">
                        <div class="mb-2">
                            <strong><?= __('Equipamento:') ?></strong> <span id="modal_computer_name" class="text-muted ms-1"></span>
                        </div>
                        <div class="mb-4">
                            <strong><?= __('Manutenção:') ?></strong> <span id="modal_maintenance_name" class="text-muted ms-1"></span>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_status_choice" class="form-label fw-bold"><?= __('Status da Manutenção') ?> <span class="text-danger">*</span></label>
                            <select name="status_choice" id="modal_status_choice" class="form-select" required>
                                <option value="solved" selected><?= __('Solucionado (Concluir e agendar próxima)') ?></option>
                                <option value="pending"><?= __('Pendente (Colocar chamado em espera)') ?></option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="modal_content" id="modal_content_label" class="form-label fw-bold"><?= __('Procedimento Efetuado (Solução)') ?> <span class="text-danger">*</span></label>
                            <textarea name="content" id="modal_content" class="form-control" rows="5" required placeholder="<?= __('Descreva detalhadamente o procedimento realizado na manutenção preventiva...') ?>"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer bg-body-tertiary border-top-0">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><?= __('Cancelar') ?></button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i><?= __('Confirmar e Salvar') ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </div> <!-- .card-body -->
    <div class="card-footer text-center py-3 text-muted border-top-0" style="background-color: var(--tblr-bg-surface-secondary, #f8f9fa);">
        <i class="fas fa-code me-1"></i> <?= __('Developed by GLPI Community') ?>
    </div>
</div> <!-- .card -->

<!-- Scripts JavaScript -->
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
<script>
const computersData = <?php echo json_encode(array_values($all_computers)); ?>;
const blockedComputers = <?php echo json_encode($blocked_computers); ?>;
const parentEntityMap = <?php echo json_encode($parent_entities_map); ?>;

function isAncestorOrSame(ancestorId, descendantId) {
    let current = parseInt(descendantId);
    ancestorId = parseInt(ancestorId);
    while (current >= 0) {
        if (current === ancestorId) {
            return true;
        }
        if (current === 0 || !parentEntityMap.hasOwnProperty(current)) {
            break;
        }
        current = parseInt(parentEntityMap[current]);
    }
    return false;
}

document.addEventListener('DOMContentLoaded', function() {
    // Aba Painel - Toggle de filtros
    const toggleFiltersBtn = document.getElementById('toggleFilters');
    const advancedFilters = document.getElementById('advancedFilters');
    
    if (toggleFiltersBtn && advancedFilters) {
        toggleFiltersBtn.addEventListener('click', function() {
            if (advancedFilters.style.display === 'none') {
                advancedFilters.style.display = 'block';
                toggleFiltersBtn.classList.remove('btn-outline-info');
                toggleFiltersBtn.classList.add('btn-info');
            } else {
                advancedFilters.style.display = 'none';
                toggleFiltersBtn.classList.remove('btn-info');
                toggleFiltersBtn.classList.add('btn-outline-info');
            }
        });
        
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.has('filter_applied')) {
            advancedFilters.style.display = 'block';
            toggleFiltersBtn.classList.remove('btn-outline-info');
            toggleFiltersBtn.classList.add('btn-info');
        }
    }
    
    // Aba Cadastro - Inicialização de Datepickers e fluxo Step 1 / Step 2
    if (document.getElementById('preventive_maintenance_form')) {
        // Configuração de localização do Datepicker
        $.datepicker.regional['pt-BR'] = {
            closeText: 'Fechar',
            prevText: '&#x3C;Anterior',
            nextText: 'Próximo&#x3E;',
            currentText: 'Hoje',
            monthNames: ['Janeiro','Fevereiro','Março','Abril','Maio','Junho',
            'Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'],
            monthNamesShort: ['Jan','Fev','Mar','Abr','Mai','Jun',
            'Jul','Ago','Set','Out','Nov','Dez'],
            dayNames: ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'],
            dayNamesShort: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
            dayNamesMin: ['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'],
            weekHeader: 'Sm',
            dateFormat: 'yy-mm-dd',
            firstDay: 0,
            isRTL: false,
            showMonthAfterYear: false,
            yearSuffix: ''
        };
        $.datepicker.setDefaults($.datepicker.regional['pt-BR']);

        // Datepicker Última Manutenção
        $("#last_maintenance_date").datepicker({
            dateFormat: 'yy-mm-dd',
            showAnim: 'fadeIn',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            onSelect: function(dateText) {
                $(this).val(dateText);
            },
            beforeShow: function(input, inst) {
                setTimeout(function() {
                    var button = inst.dpDiv.find('.ui-datepicker-current');
                    button.unbind('click').click(function() {
                        var today = new Date();
                        var formattedDate = $.datepicker.formatDate('yy-mm-dd', today);
                        $(input).val(formattedDate);
                        inst.dpDiv.hide();
                    });
                }, 1);
            }
        });
        
        // Datepicker Próxima Manutenção com seletor de Intervalo
        $("#next_maintenance_date").datepicker({
            dateFormat: 'yy-mm-dd',
            showAnim: 'fadeIn',
            changeMonth: true,
            changeYear: true,
            showButtonPanel: true,
            onSelect: function(dateText) {
                $(this).val(dateText);
            },
            beforeShow: function(input, inst) {
                setTimeout(function() {
                    var button = inst.dpDiv.find('.ui-datepicker-current');
                    button.unbind('click').click(function() {
                        var today = new Date();
                        var formattedDate = $.datepicker.formatDate('yy-mm-dd', today);
                        $(input).val(formattedDate);
                        inst.dpDiv.hide();
                    });
                    
                    var dpDiv = $(inst.dpDiv);
                    dpDiv.find('.ui-datepicker-interval').remove();
                    
                    var controls = $(
                        '<div class="ui-datepicker-interval">' +
                        '  <span>Intervalo:</span>' +
                        '  <select class="interval-value">' +
                        '    <option value="1">1 mês</option>' +
                        '    <option value="2">2 meses</option>' +
                        '    <option value="3">3 meses</option>' +
                        '    <option value="4">4 meses</option>' +
                        '    <option value="5">5 meses</option>' +
                        '    <option value="6" selected>6 meses</option>' +
                        '    <option value="7">7 meses</option>' +
                        '    <option value="8">8 meses</option>' +
                        '    <option value="9">9 meses</option>' +
                        '    <option value="10">10 meses</option>' +
                        '    <option value="11">11 meses</option>' +
                        '    <option value="12">1 ano</option>' +
                        '  </select>' +
                        '  <button type="button" class="apply-interval">Aplicar</button>' +
                        '</div>'
                    );
                    
                    dpDiv.prepend(controls);
                    
                    dpDiv.find('.apply-interval').click(function() {
                        var lastDate = $("#last_maintenance_date").val();
                        if (!lastDate) {
                            alert('Informe a data da última manutenção');
                            return;
                        }
                        
                        var months = parseInt(dpDiv.find('.interval-value').val());
                        var date = new Date(lastDate);
                        date.setMonth(date.getMonth() + months);
                        
                        var originalDay = new Date(lastDate).getDate();
                        if (date.getDate() !== originalDay) {
                            date.setDate(0);
                        }
                        
                        var formatted = $.datepicker.formatDate('yy-mm-dd', date);
                        $("#next_maintenance_date").val(formatted).datepicker('hide');
                    });
                }, 1);
            }
        });
        
        // Fluxo Step 1 -> Step 2
        $('#nextButton').click(function() {
            const selectVal = $('#entities_id_select').val();
            if (!selectVal) {
                alert('Selecione uma entidade');
                return;
            }
            
            const entityName = $('#entities_id_select option:selected').text();
            
            $('#entities_id').val(selectVal);
            $('#selected-entity-name').text(entityName);
            $('#selected-entity-id').text(selectVal);
            loadComputers(selectVal);
            
            $('#step1').hide();
            $('#step2').show();
        });
        
        $('#backButton').click(function() {
            $('#step2').hide();
            $('#step1').show();
        });
        
        function loadComputers(entityId) {
            const select = $('#items_id');
            select.find('option').not(':first').remove();
            
            const filteredComputers = computersData.filter(comp => {
                const isEntityMatch = parseInt(comp.entities_id) === parseInt(entityId) || 
                                       (parseInt(comp.is_recursive) === 1 && isAncestorOrSame(comp.entities_id, entityId));
                return isEntityMatch && !blockedComputers.includes(parseInt(comp.id));
            });
            
            if (filteredComputers.length > 0) {
                filteredComputers.forEach(comp => {
                    select.append(new Option(comp.name, comp.id));
                });
            } else {
                const option = new Option('Nenhum computador disponível', '');
                option.disabled = true;
                select.append(option);
            }
        }
    }
    
    // Aba Configurações - Seleção de Perfis via AJAX
    const profileForm = document.getElementById('profileSelectionForm');
    if (profileForm) {
        profileForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const checkedCount = document.querySelectorAll('#profileSelectionForm input[name="profiles[]"]:checked').length;
            if (checkedCount === 0) {
                alert('Selecione pelo menos um perfil técnico');
                return;
            }
            
            // Submete via AJAX
            const formData = new FormData(profileForm);
            const dataString = new URLSearchParams(formData).toString();
            
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'preventivemaintenance.php?tab=config', true);
            xhr.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');
            xhr.setRequestHeader('X-Glpi-Csrf-Token', document.querySelector('#profileSelectionForm input[name="_glpi_csrf_token"]').value);
            
            xhr.onload = function() {
                if (xhr.status === 200 && xhr.responseText.trim() === 'ok') {
                    window.location.href = 'preventivemaintenance.php?tab=config';
                } else {
                    alert('Erro ao salvar perfis técnicos. Tente novamente.');
                }
            };
            xhr.send(dataString);
        });
    }
    
    // Modal de Finalização de Manutenção Preventiva (Controles Bootstrap 5)
    let bsModal = null;
    const modalEl = document.getElementById('finishMaintenanceModal');
    if (modalEl) {
        bsModal = new bootstrap.Modal(modalEl);
    }
    
    // Abre o modal
    $(document).on('click', '.btn-finish-maintenance', function() {
        const ticketId = $(this).data('ticket-id');
        const computerName = $(this).data('computer-name');
        const maintenanceName = $(this).data('maintenance-name');
        
        $('#modal_ticket_id').val(ticketId);
        $('#modal_computer_name').text(computerName);
        $('#modal_maintenance_name').text(maintenanceName);
        
        // Reset do formulário
        $('#modal_status_choice').val('solved');
        $('#modal_content').val('');
        $('#modal_content_label').html('<?= __('Procedimento Efetuado (Solução)') ?> <span class="text-danger">*</span>');
        $('#modal_content').attr('placeholder', '<?= __('Descreva detalhadamente o procedimento realizado na manutenção preventiva...') ?>');
        
        if (bsModal) {
            bsModal.show();
        }
    });
    
    // Altera label e placeholder dependendo do status selecionado
    $('#modal_status_choice').change(function() {
        if ($(this).val() === 'solved') {
            $('#modal_content_label').html('<?= __('Procedimento Efetuado (Solução)') ?> <span class="text-danger">*</span>');
            $('#modal_content').attr('placeholder', '<?= __('Descreva detalhadamente o procedimento realizado na manutenção preventiva...') ?>');
        } else {
            $('#modal_content_label').html('<?= __('Motivo da Espera (Justificativa)') ?> <span class="text-danger">*</span>');
            $('#modal_content').attr('placeholder', '<?= __('Descreva detalhadamente a justificativa para colocar a manutenção em espera...') ?>');
        }
    });
    
    // Submete o formulário via AJAX
    $('#finishMaintenanceForm').submit(function(e) {
        e.preventDefault();
        
        const contentVal = $('#modal_content').val().trim();
        if (!contentVal) {
            alert('<?= __('Por favor, preencha a descrição.') ?>');
            return;
        }
        
        const formData = $(this).serialize();
        
        $.ajax({
            url: 'preventivemaintenance.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            headers: {
                'X-Glpi-Csrf-Token': $('input[name="_glpi_csrf_token"]').val()
            },
            success: function(response) {
                if (response.success) {
                    alert(response.message);
                    if (bsModal) {
                        bsModal.hide();
                    }
                    const activeTab = new URLSearchParams(window.location.search).get('tab') || 'portal';
                    window.location.href = 'preventivemaintenance.php?tab=' + activeTab;
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert('<?= __('Erro inesperado ao salvar. Tente novamente.') ?>');
            }
        });
    });
});
</script>

<?php
Html::footer();							   