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

// Verifica se o arquivo está sendo acessado diretamente (segurança GLPI)
// Check if file is being accessed directly (GLPI security)
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Classe principal do plugin que estende CommonDBTM para gerenciar manutenções preventivas
 * Main plugin class extending CommonDBTM to manage preventive maintenances
 */
class PluginPreventivemaintenancePreventivemaintenance extends CommonDBTM {

   // Ativa histórico de alterações
   // Enable change history
   public $dohistory = true;
   
   // Nome do direito de acesso
   // Access right name
   static $rightname = 'plugin_preventivemaintenance';

   /**
    * Retorna o nome do tipo do item (singular/plural)
    * Returns the type name of the item (singular/plural)
    */
   static function getTypeName($nb = 0) {
      return _n('Preventive Maintenance', 'Preventive Maintenances', $nb, 'preventivemaintenance');
   }

   /**
    * Retorna o nome da tabela no banco de dados
    * Returns the database table name
    */
   static function getTable($classname = null) {
      return 'glpi_plugin_preventivemaintenance_preventivemaintenances';
   }

   /**
    * Prepara os dados antes da exclusão (validações de segurança)
    * Prepares data before deletion (security validations)
    */
   public function prepareInputForDelete($input) {
    // Verificação adicional de segurança
    // Additional security check
    if (!isset($input['id'])) {
        Session::addMessageAfterRedirect(
            __('ID do registro não especificado'),
            false,
            ERROR
        );
        return false;
    }
    
    // Verifica se o registro existe
    // Check if record exists
    if (!$this->getFromDB($input['id'])) {
        Session::addMessageAfterRedirect(
            __('Registro não encontrado'),
            false,
            ERROR
        );
        return false;
    }
    
    return $input;
   }

   /**
    * Retorna a URL do formulário do item
    * Returns the item form URL
    */
   public static function getItemTypeFormURL($full = true) {
    global $CFG_GLPI;
    return ($full ? $CFG_GLPI['root_doc'] : '') . 
           '/plugins/preventivemaintenance/front/preventivemaintenance.form.php';
   }

   /**
    * Retorna o nome do item (usado para exibição)
    * Returns the item name (used for display)
    */
   function getName($options = []) {
      return $this->fields['comment'];
   }

   /**
    * Verifica permissão de criação
    * Checks create permission
    */
    public static function canCreate(): bool {
       return Session::haveRight(self::$rightname, CREATE);
    }
 
    /**
     * Verifica permissão de visualização
     * Checks view permission
     */
    public static function canView(): bool {
       return Session::haveRight(self::$rightname, READ);
    }
 
    /**
     * Verifica permissão de atualização
     * Checks update permission
     */
    public static function canUpdate(): bool {
       return Session::haveRight(self::$rightname, UPDATE);
    }
 
    /**
     * Verifica permissão de exclusão (com tratamento especial para Super-Admin)
     * Checks delete permission (with special handling for Super-Admin)
     */
    public static function canDelete(): bool {
     // Permissão sempre verdadeira para Super-Admin
     // Always true for Super-Admin
     if (isset($_SESSION['glpiactiveprofile']['name']) && 
         $_SESSION['glpiactiveprofile']['name'] == 'Super-Admin') {
         return true;
     }
     return Session::haveRight(self::$rightname, DELETE);
    }

   /**
    * Exibe o formulário de cadastro/edição
    * Displays the add/edit form
    */
   public function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

    // Campo de seleção de computador
    // Computer selection field
    echo "<tr class='tab_bg_1'><td>".__('Computer')."</td><td>";
    Computer::dropdown([
        'name'   => 'items_id',
        'value'  => $this->fields['items_id'] ?? 0,
        'entity' => $this->fields['entities_id'] ?? $_SESSION['glpiactive_entity'],
        'condition' => ['is_deleted' => 0]
    ]);
    echo "</td></tr>";

    // Campo de nome/comentário
    // Name/comment field
    echo "<tr class='tab_bg_1'><td>" . __('Nome') . "</td><td><input type='text' name='comment' value='" . $this->fields['comment'] . "'></td></tr>";

    // Campo de data da última manutenção
    // Last maintenance date field
    echo "<tr class='tab_bg_1'><td>" . __('Última Manutenção') . "</td><td>";
    Html::showDateField("last_maintenance_date", ['value' => $this->fields['last_maintenance_date'] ?? '']);
    echo "</td></tr>";

    // Campo de data da próxima manutenção
    // Next maintenance date field
    echo "<tr class='tab_bg_1'><td>" . __('Próxima Manutenção') . "</td><td>";
    Html::showDateField("next_maintenance_date", ['value' => $this->fields['next_maintenance_date'] ?? '']);
    echo "</td></tr>";

    // Campo de seleção do técnico responsável
    // Technician selection field
    echo "<tr class='tab_bg_1'><td>" . __('Técnico Responsável') . "</td><td>";
    User::dropdown([
       'name'  => 'technician_id',
       'value' => $this->fields['technician_id'] ?? 0
    ]);
    echo "</td></tr>";

    // Botões do formulário
    // Form buttons
    $this->showFormButtons($options);
    return true;
   }
   
   /**
    * Exibe valores específicos para determinados campos (especialmente o status)
    * Displays specific values for certain fields (especially status)
    */
   public static function getSpecificValueToDisplay($field, $values, array $options = []) {
    if ($field == 'status') {
        // Verifica se há computador vinculado
        // Checks if there's a linked computer
        if (empty($values['items_id'])) {
            return '<span class="state_undefined"><i class="fas fa-question-circle"></i> '.__('No Computer').'</span>';
        }

        // Verifica se tem datas válidas
        // Checks for valid dates
        if (empty($values['last_maintenance_date']) || empty($values['next_maintenance_date'])) {
            return '<span class="state_pending"><i class="fas fa-clock"></i> '.__('Not Scheduled').'</span>';
        }

        $last = strtotime($values['last_maintenance_date']);
        $next = strtotime($values['next_maintenance_date']);
        $now = time();

        // Cálculo do tempo restante
        // Days remaining calculation
        $days_remaining = round(($next - $now) / (60 * 60 * 24));
        
        // Determinar o estado
        // Determine status
        if ($days_remaining < 0) {
            return '<span class="state_overdue"><i class="fas fa-exclamation-triangle"></i> '.__('Overdue').' ('.abs($days_remaining).' '.__('days').')</span>';
        } elseif ($days_remaining <= 7) {
            return '<span class="state_due_soon"><i class="fas fa-clock"></i> '.__('Due Soon').' ('.$days_remaining.' '.__('days').')</span>';
        } else {
            return '<span class="state_ok"><i class="fas fa-check-circle"></i> '.__('On Track').' ('.$days_remaining.' '.__('days').')</span>';
        }
    }
    return parent::getSpecificValueToDisplay($field, $values, $options);
   }
   
   /**
    * Valida os dados antes de adicionar um novo registro
    * Validates data before adding a new record
    */
   public function prepareInputForAdd($input) {
    // Validação obrigatória do computador
    // Computer validation
    if (empty($input['items_id']) || $input['items_id'] == 0) {
        Session::addMessageAfterRedirect(
            __('You must select a valid computer', 'preventivemaintenance'), 
            false, 
            ERROR
        );
        return false;
    }

    // Validação das datas
    // Date validation
    if (!empty($input['last_maintenance_date']) && 
        !empty($input['next_maintenance_date']) &&
        $input['next_maintenance_date'] <= $input['last_maintenance_date']) {
        Session::addMessageAfterRedirect(
            __('Next maintenance date must be after last maintenance date', 'preventivemaintenance'),
            false,
            ERROR
        );
        return false;
    }

    // Definir valores padrão
    // Set default values
    $input['itemtype'] = 'Computer';
    $input['entities_id'] = $_SESSION['glpiactive_entity'] ?? 0;
    $input['is_recursive'] = 0;

    return $input;
   }

   /**
    * Ajustes após carregar dados do banco
    * Adjustments after loading data from database
    */
   public function post_getFromDB() {
    // Corrige visualização de registros antigos
    // Fixes display for old records
    if ($this->fields['items_id'] == 0) {
        $this->fields['items_id'] = '';
    }
    if ($this->fields['last_maintenance_date'] == '0000-00-00') {
        $this->fields['last_maintenance_date'] = '';
    }
    if ($this->fields['next_maintenance_date'] == '0000-00-00') {
        $this->fields['next_maintenance_date'] = '';
    }
   }

   /**
    * Define as opções de busca/pesquisa
    * Defines search options
    */
   public function getSearchOptionsNew() {
    $options = parent::getSearchOptionsNew();

    // Opção para buscar por computador
    // Option to search by computer
    $options[] = [
        'id'                 => '2',
        'table'              => 'glpi_computers',
        'field'              => 'name',
        'name'               => __('Computer'),
        'datatype'           => 'dropdown',
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
            'beforejoin' => [
                'table'      => 'glpi_plugin_preventivemaintenance_preventivemaintenances',
                'joinparams' => [
                    'jointype' => 'itemtype_item'
                ]
            ]
        ]
    ];

    // Opção para buscar por técnico
    // Option to search by technician
    $options[] = [
        'id'                 => '3',
        'table'              => 'glpi_users',
        'field'              => 'name',
        'name'               => __('Technician'),
        'datatype'           => 'dropdown',
        'forcegroupby'       => true,
        'massiveaction'      => false,
        'joinparams'         => [
            'beforejoin' => [
                'table'      => 'glpi_plugin_preventivemaintenance_preventivemaintenances',
                'joinparams' => [
                    'jointype' => 'itemtype_item'
                ]
            ]
        ]
    ];

    // Opção para buscar por data da última manutenção
    // Option to search by last maintenance date
    $options[] = [
        'id'                 => '4',
        'table'              => 'glpi_plugin_preventivemaintenance_preventivemaintenances',
        'field'              => 'last_maintenance_date',
        'name'               => __('Last Maintenance'),
        'datatype'           => 'date'
    ];

    // Opção para buscar por data da próxima manutenção
    // Option to search by next maintenance date
    $options[] = [
        'id'                 => '5',
        'table'              => 'glpi_plugin_preventivemaintenance_preventivemaintenances',
        'field'              => 'next_maintenance_date',
        'name'               => __('Next Maintenance'),
        'datatype'           => 'date',
        'searchtype'         => 'equals'
    ];

    // Opção para mostrar o status (calculado)
    // Option to show status (calculated)
    $options[] = [
        'id'                 => '100',
        'name'               => __('Status'),
        'field'              => 'status',
        'nosearch'           => true,
        'nodisplay'          => false,
        'datatype'           => 'specific',
        'additionalfields'   => ['items_id', 'last_maintenance_date', 'next_maintenance_date']
    ];

    return $options;
   }

   /**
    * Processa resultados de busca personalizados (especialmente a barra de status)
    * Processes custom search results (especially the status bar)
    */
   public function getSearchResultNew($field, $values, $options = []) {
      if ($field === 'status_bar') {
         $last = strtotime($this->fields['last_maintenance_date']);
         $next = strtotime($this->fields['next_maintenance_date']);
         $now  = time();

         if (!$last || !$next || $next <= $last) {
            return '⚠️';
         }

         $percent = round(($now - $last) / ($next - $last) * 100);
         $percent = max(0, min(100, $percent));

         $color = 'green';
         if ($percent >= 95) $color = 'orange';
         if ($percent >= 99) $color = 'red';

         return "<div style='width:100%; background:#eee; border-radius:4px; height:15px;'>
                  <div style='width:{$percent}%; background:{$color}; height:100%; border-radius:4px;'></div>
                 </div>";
      }

      return parent::getSearchResultNew($field, $values, $options);
      error_log("getSearchResult: $field");
   }

   public static function getPluginConfig($name) {
      global $DB;
      
      $criteria = [
          'SELECT' => ['value'],
          'FROM' => 'glpi_plugin_preventivemaintenance_config',
          'WHERE' => ['name' => $name],
          'LIMIT' => 1
      ];
      
      $iterator = $DB->request($criteria);
      
      if (count($iterator)) {
          $data = $iterator->current();
          return $data['value'];
      }
      
      return false;
   }

   public static function updatePluginConfig($name, $value) {
      global $DB;
      
      $existing = self::getPluginConfig($name);
      $now = date('Y-m-d H:i:s');
      
      if ($existing !== false) {
          return $DB->update('glpi_plugin_preventivemaintenance_config', [
              'value' => $value,
              'date_mod' => $now
          ], [
              'name' => $name
          ]);
      } else {
          return $DB->insert('glpi_plugin_preventivemaintenance_config', [
              'name' => $name,
              'value' => $value,
              'date_creation' => $now,
              'date_mod' => $now
          ]);
      }
   }

   public static function hasOpenMaintenanceTicket($computer_id, $maintenance_name) {
       global $DB;
       
       if (empty($computer_id) || !is_numeric($computer_id) || empty($maintenance_name)) {
           return false;
       }
       
       try {
           $criteria = [
               'SELECT' => ['glpi_tickets.id'],
               'FROM' => 'glpi_tickets',
               'INNER JOIN' => [
                   'glpi_items_tickets' => [
                       'ON' => [
                           'glpi_items_tickets' => 'tickets_id',
                           'glpi_tickets' => 'id'
                       ]
                   ]
               ],
               'WHERE' => [
                   'glpi_items_tickets.items_id' => (int)$computer_id,
                   'glpi_items_tickets.itemtype' => 'Computer',
                   'glpi_tickets.name' => ['LIKE', '%' . $DB->escape($maintenance_name) . '%'],
                   ['NOT' => ['glpi_tickets.status' => [Ticket::CLOSED, Ticket::SOLVED]]]
               ],
               'LIMIT' => 1
           ];
           
           $iterator = $DB->request($criteria);
           
           if (count($iterator)) {
               return true;
           }
           
            $criteria = [
                'SELECT' => ['glpi_plugin_preventivemaintenance_tickets.id'],
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
                    'glpi_plugin_preventivemaintenance_tickets.computer_id' => (int)$computer_id,
                    'glpi_plugin_preventivemaintenance_tickets.maintenance_name' => $maintenance_name,
                    ['NOT' => ['glpi_tickets.status' => [Ticket::CLOSED, Ticket::SOLVED]]]
                ],
                'LIMIT' => 1
            ];
            
            $iterator = $DB->request($criteria);
            
            return count($iterator) > 0;
       } catch (Exception $e) {
           Toolbox::logInFile('php-errors', "Erro ao verificar tickets existentes: " . $e->getMessage() . "\n");
           return false;
       }
   }

   public static function registerMaintenanceTicket($ticket_id, $computer_id, $maintenance_name) {
      global $DB;
      
      try {
          $DB->insert('glpi_plugin_preventivemaintenance_tickets', [
              'ticket_id' => (int)$ticket_id,
              'computer_id' => (int)$computer_id,
              'maintenance_name' => $maintenance_name,
              'date_creation' => date('Y-m-d H:i:s')
          ]);
          return true;
      } catch (Exception $e) {
          Toolbox::logInFile('php-errors', "Erro ao registrar o ticket: " . $e->getMessage() . "\n");
          return false;
      }
   }

   public static function updateMaintenanceOnTicketResolution($ticket_id) {
      global $DB;
      
      try {
          $criteria = [
              'SELECT' => ['computer_id', 'maintenance_name'],
              'FROM' => 'glpi_plugin_preventivemaintenance_tickets',
              'WHERE' => [
                  'ticket_id' => (int)$ticket_id
              ],
              'LIMIT' => 1
          ];
          
          $iterator = $DB->request($criteria);
          
          if (count($iterator)) {
              $data = $iterator->current();
              $computer_id = $data['computer_id'];
              $maintenance_name = $data['maintenance_name'];
              
              $pm = new self();
              $maintenance = $pm->find([
                  'items_id' => $computer_id,
                  'name' => $maintenance_name
              ]);
              
              if (count($maintenance)) {
                  $maintenance_data = current($maintenance);
                  $maintenance_id = $maintenance_data['id'];
                  
                  $ticket = new Ticket();
                  if ($ticket->getFromDB($ticket_id)) {
                      $solvedate = $ticket->getField('solvedate');
                      
                      if (!empty($solvedate)) {
                          $interval_days = (int)$maintenance_data['maintenance_interval'];
                          if ($interval_days <= 0) {
                              $interval_days = 30; // default to 30 days
                          }
                          
                          $new_next_date = date('Y-m-d H:i:s', strtotime($solvedate) + ($interval_days * 24 * 60 * 60));
                          
                          $input = [
                              'id' => $maintenance_id,
                              'name' => $maintenance_name,
                              'last_maintenance_date' => $solvedate,
                              'next_maintenance_date' => $new_next_date
                          ];
                          
                          if ($pm->update($input)) {
                              $DB->delete('glpi_plugin_preventivemaintenance_tickets', [
                                  'ticket_id' => $ticket_id
                              ]);
                              return true;
                          }
                      }
                  }
              }
          }
          return false;
      } catch (Exception $e) {
          Toolbox::logInFile('php-errors', "Erro ao atualizar a manutenção: " . $e->getMessage() . "\n");
          return false;
      }
   }

    public static function cleanResolvedMaintenanceTickets() {
       global $DB;
       
       try {
           // Clean up orphaned tickets that no longer exist in GLPI tickets table
           $sub_query = new \Glpi\DBAL\QuerySubQuery([
               'SELECT' => ['id'],
               'FROM'   => 'glpi_tickets'
           ]);
           $DB->delete('glpi_plugin_preventivemaintenance_tickets', [
               'NOT' => ['ticket_id' => $sub_query]
           ]);

           $criteria = [
              'SELECT' => ['glpi_tickets.id', 'glpi_tickets.solvedate'],
              'FROM' => 'glpi_tickets',
              'INNER JOIN' => [
                  'glpi_plugin_preventivemaintenance_tickets' => [
                      'ON' => [
                          'glpi_plugin_preventivemaintenance_tickets' => 'ticket_id',
                          'glpi_tickets' => 'id'
                      ]
                  ]
              ],
              'WHERE' => [
                  'glpi_tickets.status' => [Ticket::CLOSED, Ticket::SOLVED]
              ]
          ];
          
          $iterator = $DB->request($criteria);
          
          foreach ($iterator as $data) {
              self::updateMaintenanceOnTicketResolution($data['id']);
              
              $DB->delete('glpi_plugin_preventivemaintenance_tickets', [
                  'ticket_id' => $data['id']
              ]);
          }
          
          return true;
      } catch (Exception $e) {
          Toolbox::logInFile('php-errors', "Erro ao limpar tickets resolvidos: " . $e->getMessage() . "\n");
          return false;
      }
   }

   public static function createMaintenanceTicket($computer_id, $maintenance_name, $technician_id, $entity_id = 0) {
      if (self::hasOpenMaintenanceTicket($computer_id, $maintenance_name)) {
          return false;
      }
      
      $ticket = new Ticket();
      
      $input = [
          'name' => sprintf(__('Preventive maintenance required: %s'), $maintenance_name),
          'content' => sprintf(__('Computer requires preventive maintenance for: %s'), $maintenance_name),
          'items_id' => [
              'Computer' => [(int)$computer_id]
          ],
          'itemtype' => 'Computer',
          'type' => Ticket::INCIDENT_TYPE,
          'status' => Ticket::INCOMING,
          'urgency' => 5,
          'impact' => 5,
          'priority' => 5,
          'requesttypes_id' => 1,
          'users_id_recipient' => class_exists('Session') ? Session::getLoginUserID() : 0,
          'entities_id' => $entity_id ?: (isset($_SESSION['glpiactive_entity']) ? $_SESSION['glpiactive_entity'] : 0),
          'date' => date('Y-m-d H:i:s')
      ];
      
      if (!empty($technician_id)) {
          $input['_observers']['_users_id_observer'] = [(int)$technician_id];
      }
      
      try {
          $ticket_id = $ticket->add($input);
          if ($ticket_id) {
              self::registerMaintenanceTicket($ticket_id, $computer_id, $maintenance_name);
              return $ticket_id;
          }
          return false;
      } catch (Exception $e) {
          Toolbox::logInFile('php-errors', "Erro ao criar o ticket: " . $e->getMessage() . "\n");
          return false;
      }
   }

   public static function updateMaintenanceAfterTicket($ticket) {
      if (!($ticket instanceof Ticket)) {
         return;
      }
      $ticket_id = $ticket->getID();
      $status = $ticket->getField('status');
      if (in_array($status, [Ticket::SOLVED, Ticket::CLOSED])) {
         self::updateMaintenanceOnTicketResolution($ticket_id);
      }
   }

   static function cronInfo($name) {
      switch ($name) {
         case 'preventivemaintenance':
            return [
               'description' => __('Gera chamados de manutenção preventiva pendentes', 'preventivemaintenance')
            ];
      }
      return [];
   }

   static function cronPreventivemaintenance($task) {
      $auto_ticket = self::getPluginConfig('auto_ticket');
      if ($auto_ticket !== '1') {
         return 0; // Not enabled
      }
      
      self::cleanResolvedMaintenanceTickets();
      
      $pm = new self();
      $all_maintenances = $pm->find([], 'next_maintenance_date ASC');
      
      $processed = 0;
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
                  $ticket_id = self::createMaintenanceTicket(
                     $item['items_id'],
                     $item['name'],
                     $item['technician_id'],
                     $item['entities_id']
                  );
                  if ($ticket_id) {
                     $processed++;
                     $task->addVolume(1);
                  }
               }
            }
         }
      }
      
      return $processed > 0 ? 1 : 0;
   }

}