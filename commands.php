<?php
include_once 'lang.php';
if (isset($bot)) {
    if (isset($chat) && isset($message) && isset($user)) {
        if ($chat->id > 0) {
            if (!isset($messages[$user->db->lang])) {
                if (!isset($callback)) {
                    $count = 0;
                    $kb = [];
                    foreach ($messages as $lang_code => $strings) {
                        $line[] = ['text' => $strings['lang_name'], 'callback_data' => '/setlang ' . $lang_code];
                        if ($count % 2 == 1 || $count === count($messages) - 1) {
                            $kb[] = $line;
                            unset($line);
                        }
                        $count++;
                    }
                    $bot->sendMessage($chat, $messages['en']['choose_lang'], $kb);
                } else {
                    if (stripos($callback->data, '/setlang') === 0) {
                        $lang = explode(' ', $callback->data)[1];
                        if (isset($messages[$lang])) {
                            $user->db->setColumn('lang', $lang);
                            $kb = [
                                [['text' => $messages[$lang]['next'], 'callback_data' => '/start']],
                            ];
                            $bot->editMessageText($chat, $message, $messages[$lang]['lang_chosen'], $kb);
                            $callback->answer();
                        } else {
                            $callback->answer($messages['en']['lang_404']);
                        }
                    }
                }
            } else {
                $l = $messages[$user->db->lang];
                if (stripos($message->text, '/start') === 0 || (isset($callback) && stripos($callback->data, '/start') === 0)) {
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $kb = [
                        [['text' => $l['manage_bots'], 'callback_data' => '/bots']],
                        [['text' => $l['set_webhook'], 'callback_data' => '/setwebhook'], ['text' => $l['info_webhook'], 'callback_data' => '/webhookinfo']],
                        [['text' => $l['delete_webhook'], 'callback_data' => '/deletewb'], ['text' => $l['delete_updates'], 'callback_data' => '/deleteup']],
                        [['text' => $l['settings'], 'callback_data' => '/settings']],
                    ];
                    if (isset($callback)) {
                        $bot->editMessageText($chat, $message, $l['start'], $kb);
                        $callback->answer();
                    } else {
                        $bot->sendMessage($chat, $l['start'], $kb);
                    }
                } elseif (isset($callback) && stripos($callback->data, '/settings') === 0) {
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $kb = [
                        [['text' => $l['manage_bots'], 'callback_data' => '/bots']],
                        [['text' => $l['wb_models'], 'callback_data' => '/models']],
                        [['text' => $l['change_lang'], 'callback_data' => '/setlang']],
                        [['text' => $l['go_back'], 'callback_data' => '/start']],
                    ];
                    $bot->editMessageText($chat, $message, $l['settings_text'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data, '/models') === 0) {
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $p = explode(' ', $callback->data)[1];
                    if (!$p) $p = 1;
                    $kb[] = [['text' => $l['add_model'], 'callback_data' => '/addmodel']];
                    $models = json_decode($user->db->models,true);
                    $c = count($models);
                    $embool = ['','✅ '];
                    if ($c > 0) {
                        $min = ($p - 1) * 6;
                        if ($c < $min) {
                            $callback->answer($l['404_page'], true);
                            exit;
                        }
                        $max = $p * 6;
                        if ($c < $max) $max = $c;
                        $count = 0;
                        foreach ($models as $modelID => $modelInfo) {
                            if ($count >= $min && $count < $max) {
                                $line[] = ['text' => $embool[$user->db->model == $modelID] . $modelInfo['name'], 'callback_data' => '/managemodel ' . $modelID];
                                if ($count % 2 === 1 || $count === $max - 1) {
                                    $kb[] = $line;
                                    unset($line);
                                }
                            } elseif ($count >= $max) {
                                break;
                            }
                            $count++;
                        }

                        if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/models' . ($p - 1)];
                        if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/models ' . ($p + 1)];
                        if (isset($nav)) $kb[] = $nav;
                    }
                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/setwebhook']];
                    $bot->editMessageText($chat, $message, $l['models_text'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data,'/managemodel') === 0) {
                    $model = explode(' ',$callback->data)[1];
                    $models = json_decode($user->db->models,true);
                    if (!isset($models[$model])) {
                        $callback->answer($l['404_page']);
                    } else {
                        if (isset(explode(' ',$callback->data)[2])) {
                            $callback->answer($l['model_selected'],true);
                            $user->db->setColumn('model',$model);
                            $user->db->model = $model;
                        }
                        $user->db->model == $model ? $t = $l['selected'] : $t = $l['select'];

                        $kb[] = [['text' => $t, 'callback_data' => '/managemodel ' . $model . ' select']];
                        if ($model > 0) $kb[] = [['text' => $l['delete_model'], 'callback_data' => '/deletemodel ' . $model]];
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/models']];
                        $m = $models[$model];
                        $bot->editMessageText($chat, $message, str_replace(['&name','&limit','&format'],[$m['name'],$m['limit'],$m['format']],$l['managemodel_text']), $kb);
                        $callback->answer();
                    }
                } elseif (isset($callback) && stripos($callback->data,'/deletemodel') === 0) {
                    $model = explode(' ',$callback->data)[1];
                    $models = json_decode($user->db->models,true);
                    if (!isset($models[$model])|| $model == 0) {
                        $callback->answer($l['404_page']);
                    } else {
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/models']];
                        unset($models[$model]);
                        $user->db->setColumn('models',json_encode($models));
                        $bot->editMessageText($chat, $message, $l['model_deleted'], $kb);
                        $callback->answer();
                    }
                } elseif (isset($callback) && stripos($callback->data,'/addmodel') === 0) {
                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/models']];
                    $user->db->setColumn('state','addmodel{}');
                    $bot->editMessageText($chat, $message, $l['addmodel_text'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data, '/setlang') === 0) {
                    $lang = explode(' ',$callback->data)[1];
                    if (isset($lang)) {
                        if (isset($messages[$lang])) {
                            $user->db->setColumn('lang', $lang);
                            $user->db->lang = $lang;
                            $l = $messages[$lang];
                        } else {
                            $callback->answer($messages['en']['lang_404']);
                        }
                    }
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $count = 0;
                    $kb = [];
                    $embool = ['','✅ '];
                    foreach ($messages as $lang_code => $strings) {
                        $line[] = ['text' => $embool[$lang_code == $user->db->lang] . $strings['lang_name'], 'callback_data' => '/setlang ' . $lang_code];
                        if ($count % 2 == 1 || $count === count($messages) - 1) {
                            $kb[] = $line;
                            unset($line);
                        }
                        $count++;
                    }
                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/settings']];
                    $bot->editMessageText($chat,$message, $l['choose_lang'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data, '/bots') === 0) {
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $p = explode(' ', $callback->data)[1];
                    if (!$p) $p = 1;
                    $kb[] = [['text' => $l['add_bot'], 'callback_data' => '/addbot']];
                    $bots = json_decode($user->db->bots, true);
                    $c = count($bots);
                    if ($c > 0) {
                        $min = ($p - 1) * 6;
                        if ($c < $min) {
                            $callback->answer($l['404_page'], true);
                            exit;
                        }
                        $max = $p * 6;
                        if ($c < $max) $max = $c;
                        $count = 0;
                        foreach ($bots as $botID => $botInfo) {
                            if ($count >= $min && $count < $max) {
                                $line[] = ['text' => $botInfo['username'], 'callback_data' => '/managebot ' . $botID];
                                if ($count % 2 === 1 || $count === $max - 1) {
                                    $kb[] = $line;
                                    unset($line);
                                }
                            } elseif ($count >= $max) {
                                break;
                            }
                            $count++;
                        }
                        if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/bots ' . ($p - 1)];
                        if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/bots ' . ($p + 1)];
                        if (isset($nav)) $kb[] = $nav;
                    }
                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                    $bot->editMessageText($chat, $message, $l['bots_text'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data, '/managebot') === 0) {
                    if ($user->db->state != '') $user->db->setColumn('state', '');
                    $botid = explode(' ', $callback->data)[1];
                    $bots = json_decode($user->db->bots, true);
                    if (!isset($bots[$botid])) {
                        $callback->answer($l['404_page'], true);
                    } else {
                        $kb = [
                            [['text' => $l['set_webhook'], 'callback_data' => '/setwebhook ' . $botid], ['text' => $l['info_webhook'], 'callback_data' => '/webhookinfo ' . $botid]],
                            [['text' => $l['delete_webhook'], 'callback_data' => '/deletewb ' . $botid], ['text' => $l['delete_updates'], 'callback_data' => '/deleteup ' . $botid]],
                            [['text' => $l['delete'], 'callback_data' => '/delete ' . $botid]],
                            [['text' => $l['go_back'], 'callback_data' => '/bots']],
                        ];
                        $bot->editMessageText($chat, $message, str_replace(['&name', '&username', '&token', '&id'], [$bots[$botid]['name'], $bots[$botid]['username'], $bots[$botid]['token'], $botid], $l['bot_panel']), $kb);
                    }
                } elseif (isset($callback) && stripos($callback->data, '/delete ') === 0) {
                    $botid = explode(' ', $callback->data)[1];
                    $bots = json_decode($user->db->bots, true);
                    if (!isset($bots[$botid])) {
                        $callback->answer($l['404_page'], true);
                    } else {
                        if (isset(explode(' ', $callback->data)[2])) {
                            unset($bots[$botid]);
                            $user->db->setColumn('bots', json_encode($bots));
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/bots']],
                            ];
                            $bot->editMessageText($user, $message, $l['bot_deleted'], $kb);
                        } else {
                            $kb = [
                                [['text' => '✅', 'callback_data' => '/delete ' . $botid . ' remove'], ['text' => '❌', 'callback_data' => '/managebot ' . $botid]],
                            ];
                            $bot->editMessageText($user, $message, str_replace('&username', $bots[$botid]['username'], $l['delete_confirm']), $kb);
                        }
                    }
                } elseif (isset($callback) && stripos($callback->data, '/addbot') === 0) {
                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                    $user->db->setColumn('state', 'addbot');
                    $bot->editMessageText($user, $message, $l['add_bot_text'], $kb);
                    $callback->answer();
                } elseif (isset($callback) && stripos($callback->data, '/webhookinfo') === 0) {
                    $p = explode(' ', $callback->data)[1];
                    if (!$p) $p = 1;
                    if ($p > 1000) {
                        if ($user->db->state != '') $user->db->setColumn('state', '');
                        $botid = $p;
                        $bots = json_decode($user->db->bots, true);
                        if (!isset($bots[$botid])) {
                            $callback->answer($l['404_page'], true);
                        } else {
                            $tok = $bots[$botid]['token'];
                            $botApi = new botApi('bot' . $tok, $config);
                            $wbinfo = $botApi->getWebhookInfo();
                            if (!$wbinfo->ok) {
                                unset($bots[$botid]);
                                $user->db->setColumn('bots', json_encode($bots));
                                $bot->editMessageText($user, $message, $l['e_token_expired']);
                                $callback->answer();
                            } else {
                                $kb = [
                                    [['text' => $l['manage_bots'], 'callback_data' => '/bots']],
                                    [['text' => $l['info_webhook'], 'callback_data' => '/webhookinfo']],
                                    [['text' => $l['refresh'], 'callback_data' => '/webhookinfo ' . $botid]],
                                ];
                                $callback->answer();
                                $bot->editMessageText($user, $message, str_replace(['&name', '&username', '&id', '&url', '&updates', '&limit', '&error', '&date', '&token'], [$bots[$botid]['name'], $bots[$botid]['username'], $botid, $wbinfo->url, $wbinfo->pending_update_count, $wbinfo->max_connections, $wbinfo->last_error_message, date('H:i d/m/Y', $wbinfo->last_error_date), $tok], $l['wbinfo']), $kb);
                            }
                        }

                    } else {
                        $kb[] = [['text' => $l['add_bot'], 'callback_data' => '/addbot']];
                        $bots = json_decode($user->db->bots, true);
                        $c = count($bots);
                        if ($c > 0) {
                            $min = ($p - 1) * 6;
                            if ($c < $min) {
                                $callback->answer($l['404_page'], true);
                                exit;
                            }
                            $max = $p * 6;
                            if ($c < $max) $max = $c;
                            $count = 0;
                            foreach ($bots as $botID => $botInfo) {
                                if ($count >= $min && $count < $max) {
                                    $line[] = ['text' => $botInfo['username'], 'callback_data' => '/webhookinfo ' . $botID];
                                    if ($count % 2 === 1 || $count === $max - 1) {
                                        $kb[] = $line;
                                        unset($line);
                                    }
                                } elseif ($count >= $max) {
                                    break;
                                }
                                $count++;
                            }
                            if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/bots ' . ($p - 1)];
                            if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/bots ' . ($p + 1)];
                            if (isset($nav)) $kb[] = $nav;
                        }
                        $user->db->setColumn('state', 'infowb');
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                        $bot->editMessageText($chat, $message, $l['webhookinfo_text'], $kb);
                        $callback->answer();
                    }
                } elseif (isset($callback) && stripos($callback->data, '/deletewb') === 0) {
                    $p = explode(' ', $callback->data)[1];
                    if (!$p) $p = 1;
                    if ($p > 1000) {
                        if ($user->db->state != '') $user->db->setColumn('state', '');
                        $botid = $p;
                        $bots = json_decode($user->db->bots, true);
                        if (!isset($bots[$botid])) {
                            $callback->answer($l['404_page'], true);
                        } else {
                            if (!isset(explode(' ', $callback->data)[2])) {
                                $kb = [
                                    [['text' => '✅', 'callback_data' => '/deletewb ' . $botid . ' remove'], ['text' => '❌', 'callback_data' => '/managebot ' . $botid]],
                                ];
                                $bot->editMessageText($user, $message, str_replace('&username', $bots[$botid]['username'], $l['deletewb_confirm']), $kb);
                                $callback->answer();
                            } else {
                                $tok = $bots[$botid]['token'];
                                $botApi = new botApi('bot' . $tok, $config);
                                $delwb = $botApi->deleteWebhook();
                                if (!$delwb->ok) {
                                    unset($bots[$botid]);
                                    $user->db->setColumn('bots', json_encode($bots));
                                    $bot->editMessageText($user, $message, $l['e_token_expired']);
                                    $callback->answer();
                                } else {
                                    $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                                    $bot->editMessageText($user, $message, $l['wb_deleted'], $kb);
                                    $callback->answer();
                                }
                            }
                        }
                    } else {
                        $bots = json_decode($user->db->bots, true);
                        $c = count($bots);
                        if ($c > 0) {
                            $min = ($p - 1) * 6;
                            if ($c < $min) {
                                $callback->answer($l['404_page'], true);
                                exit;
                            }
                            $max = $p * 6;
                            if ($c < $max) $max = $c;
                            $count = 0;
                            foreach ($bots as $botID => $botInfo) {
                                if ($count >= $min && $count < $max) {
                                    $line[] = ['text' => $botInfo['username'], 'callback_data' => '/deletewb ' . $botID];
                                    if ($count % 2 === 1 || $count === $max - 1) {
                                        $kb[] = $line;
                                        unset($line);
                                    }
                                } elseif ($count >= $max) {
                                    break;
                                }
                                $count++;
                            }
                            if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/bots ' . ($p - 1)];
                            if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/bots ' . ($p + 1)];
                            if (isset($nav)) $kb[] = $nav;
                        }
                        $user->db->setColumn('state', 'deletewb');
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                        $bot->editMessageText($chat, $message, $l['deletewb_text'], $kb);
                        $callback->answer();
                    }
                } elseif (isset($callback) && stripos($callback->data, '/deleteup') === 0) {
                    $p = explode(' ', $callback->data)[1];
                    if (!$p) $p = 1;
                    if ($p > 1000) {
                        if ($user->db->state != '') $user->db->setColumn('state', '');
                        $botid = $p;
                        $bots = json_decode($user->db->bots, true);
                        if (!isset($bots[$botid])) {
                            $callback->answer($l['404_page'], true);
                        } else {
                            $tok = $bots[$botid]['token'];
                            $botApi = new botApi('bot' . $tok, $config);
                            $wbinfo = $botApi->getWebhookInfo();
                            if (!$wbinfo->ok) {
                                unset($bots[$botid]);
                                $user->db->setColumn('bots', json_encode($bots));
                                $bot->editMessageText($user, $message, $l['e_token_expired']);
                                $callback->answer();
                            } else {
                                $delwb = $botApi->deleteWebhook();
                                $getUp = $botApi->sendRequest('getUpdates',['offset' => -1]);
                                $setwb = $botApi->setWebhook($wbinfo->url,false,$wbinfo->max_connections);
                                $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                                $bot->editMessageText($user, $message, $l['updates_deleted'], $kb);
                                $callback->answer();
                            }
                        }
                    } else {
                        $bots = json_decode($user->db->bots, true);
                        $c = count($bots);
                        if ($c > 0) {
                            $min = ($p - 1) * 6;
                            if ($c < $min) {
                                $callback->answer($l['404_page'], true);
                                exit;
                            }
                            $max = $p * 6;
                            if ($c < $max) $max = $c;
                            $count = $min;
                            $count = 0;
                            foreach ($bots as $botID => $botInfo) {
                                if ($count >= $min && $count < $max) {
                                    $line[] = ['text' => $botInfo['username'], 'callback_data' => '/deleteup ' . $botID];
                                    if ($count % 2 === 1 || $count === $max - 1) {
                                        $kb[] = $line;
                                        unset($line);
                                    }
                                } elseif ($count >= $max) {
                                    break;
                                }
                                $count++;
                            }
                            if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/bots ' . ($p - 1)];
                            if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/bots ' . ($p + 1)];
                            if (isset($nav)) $kb[] = $nav;
                        }
                        $user->db->setColumn('state', 'deleteup');
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                        $bot->editMessageText($chat, $message, $l['deleteup_text'], $kb);
                        $callback->answer();
                    }
                } elseif (isset($callback) && stripos($callback->data, '/setwebhook') === 0) {
                    $ex = explode(' ', $callback->data);
                    $p = $ex[1];
                    if (!$p) $p = 1;
                    $kb[] = [['text' => $l['wb_models'], 'callback_data' => '/models']];
                    if ($p > 1000) {
                        $botid = $p;
                        $bots = json_decode($user->db->bots, true);
                        if (!isset($bots[$botid])) {
                            $callback->answer($l['404_page'], true);
                        } else {
                            $tok = $bots[$botid]['token'];
                            $botApi = new botApi('bot' . $tok, $config);
                            $getMe = $botApi->getMe();
                            if (!$getMe->ok) {
                                unset($bots[$botid]);
                                $user->db->setColumn('bots', json_encode($bots));
                                $bot->editMessageText($user, $message, $l['e_token_expired']);
                                $callback->answer();
                            } else {
                                //TODO Webhook Models
                                $user->db->setColumn('state', 'setwb ' . $tok . ' 0');
                                $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                                $models = json_decode($user->db->models,true);
                                $m = $models[$user->db->model];
                                $bot->editMessageText($chat, $message, str_replace(['&name','&limit','&format'],[$m['name'],$m['limit'],$m['format']],$l['send_url']), $kb);
                                $callback->answer();
                            }
                        }
                    } else {
                        $bots = json_decode($user->db->bots, true);
                        $c = count($bots);
                        if ($c > 0) {
                            $min = ($p - 1) * 6;
                            if ($c < $min) {
                                $callback->answer($l['404_page'], true);
                                exit;
                            }
                            $max = $p * 6;
                            if ($c < $max) $max = $c;
                            $count = 0;
                            foreach ($bots as $botID => $botInfo) {
                                if ($count >= $min && $count < $max) {
                                    $line[] = ['text' => $botInfo['username'], 'callback_data' => '/setwebhook ' . $botID];
                                    if ($count % 2 === 1 || $count === $max - 1) {
                                        $kb[] = $line;
                                        unset($line);
                                    }
                                } elseif ($count >= $max) {
                                    break;
                                }
                                $count++;
                            }
                            if ($p > 1) $nav[] = ['text' => '⬅️', 'callback_data' => '/bots ' . ($p - 1)];
                            if ($c > $p * 6) $nav[] = ['text' => '➡️', 'callback_data' => '/bots ' . ($p + 1)];
                            if (isset($nav)) $kb[] = $nav;
                        }
                        $user->db->setColumn('state', 'setwb');
                        $kb[] = [['text' => $l['go_back'], 'callback_data' => '/start']];
                        $bot->editMessageText($chat, $message, $l['setwb_text'], $kb);
                        $callback->answer();
                    }
                } elseif (stripos($message->text, '/infowb') === 0) {
                    $tok = explode(' ', $message->text)[1];
                    if (!$tok) {
                        $bot->sendMessage($chat, $l['e_infowb']);
                    } else {
                        $botApi = new botApi('bot' . $tok, $config);
                        $getMe = $botApi->getMe();
                        $kb = [
                            [['text' => $l['go_back'], 'callback_data' => '/start']],
                        ];
                        if (!$getMe->ok) {
                            $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                        } else {
                            $wbinfo = $botApi->getWebhookInfo();
                            $bot->sendMessage($user, str_replace(['&name', '&username', '&id', '&url', '&updates', '&limit', '&error', '&date', '&token'], [$getMe->first_name, $getMe->username, $getMe->id, $wbinfo->url, $wbinfo->pending_update_count, $wbinfo->max_connections, $wbinfo->last_error_message, date('H:i d/m/Y', $wbinfo->last_error_date), $tok], $l['wbinfo']), $kb);
                        }
                    }

                } elseif (stripos($message->text, '/delwb') === 0) {
                    $tok = explode(' ', $message->text)[1];
                    if (!$tok) {
                        $bot->sendMessage($chat, $l['e_delwb']);
                    } else {
                        $botApi = new botApi('bot' . $tok, $config);
                        $kb = [
                            [['text' => $l['go_back'], 'callback_data' => '/start']],
                        ];
                        $delwb = $botApi->deleteWebhook();
                        if (!$delwb->ok) {
                            $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                        } else {
                            $bot->sendMessage($user, $l['wb_deleted'], $kb);
                        }
                    }
                } elseif (stripos($message->text, '/delup') === 0) {
                    $tok = explode(' ', $message->text)[1];
                    $kb = [
                        [['text' => $l['go_back'], 'callback_data' => '/start']],
                    ];
                    if (!$tok) {
                        $bot->sendMessage($chat, $l['e_delup'],$kb);
                    } else {
                        $botApi = new botApi('bot' . $tok, $config);
                        $wbinfo = $botApi->getWebhookInfo();
                        if (!$wbinfo->ok) {
                            $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                        } else {
                            $delwb = $botApi->deleteWebhook();
                            $getUp = $botApi->sendRequest('getUpdates', ['offset' => -1]);
                            $setwb = $botApi->setWebhook($wbinfo->url, false, $wbinfo->max_connections);
                            $bot->sendMessage($user, $l['updates_deleted'], $kb);
                        }
                    }
                } else {
                    if (isset($message->text)) {
                        if ($user->db->state == 'addbot') {
                            $added = new botApi('bot' . $message->text, $config);
                            $getMe = $added->getMe();
                            if (!$getMe->ok) {
                                $kb = [
                                    [['text' => $l['go_back'], 'callback_data' => '/start']],
                                ];
                                $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                            } else {
                                $bots = json_decode($user->db->bots, true);
                                $bots[$getMe->id] = ['token' => $message->text, 'username' => $getMe->username, 'name' => $getMe->first_name];
                                $bot->sendMessage($user, str_replace('&username', $getMe->username, $l['bot_added']));
                                $user->db->setColumn('state', '');
                                $user->db->setColumn('bots', json_encode($bots));
                            }
                        } elseif ($user->db->state == 'infowb') {
                            $tok = $message->text;
                            $botApi = new botApi('bot' . $tok, $config);
                            $getMe = $botApi->getMe();
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/start']],
                            ];
                            if (!$getMe->ok) {
                                $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                            } else {
                                $wbinfo = $botApi->getWebhookInfo();
                                $user->db->setColumn('state', '');
                                $bot->sendMessage($user, str_replace(['&name', '&username', '&id', '&url', '&updates', '&limit', '&error', '&date', '&token'], [$getMe->first_name, $getMe->username, $getMe->id, $wbinfo->url, $wbinfo->pending_update_count, $wbinfo->max_connections, $wbinfo->last_error_message, date('H:i d/m/Y', $wbinfo->last_error_date), $tok], $l['wbinfo']), $kb);
                            }
                        } elseif ($user->db->state == 'deletewb') {
                            $tok = $message->text;
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/start']],
                            ];
                            $botApi = new botApi('bot' . $tok, $config);
                            $delwb = $botApi->deleteWebhook();
                            if (!$delwb->ok) {
                                $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                            } else {
                                $bot->sendMessage($user, $l['wb_deleted'], $kb);
                            }
                        } elseif ($user->db->state == 'deleteup') {
                            $tok = $message->text;
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/start']],
                            ];
                            $botApi = new botApi('bot' . $tok, $config);
                            $wbinfo = $botApi->getWebhookInfo();
                            if (!$wbinfo->ok) {
                                $bot->sendMessage($user, $l['e_token_not_valid'], $kb);
                            } else {
                                $delwb = $botApi->deleteWebhook();
                                $getUp = $botApi->sendRequest('getUpdates', ['offset' => -1]);
                                $setwb = $botApi->setWebhook($wbinfo->url, false, $wbinfo->max_connections);
                                $bot->sendMessage($user, $l['updates_deleted'], $kb);
                            }
                        } elseif (stripos($user->db->state,'addmodel') === 0) {
                            $js = json_decode(ltrim($user->db->state,'addmodel'),true);
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/models']],
                            ];
                            if (!isset($js['name'])) {
                                if (strlen($message->text) > 12) {
                                    $bot->sendMessage($user,str_replace('&ch',12,$l['e_ch_limit'],$kb));
                                } else {
                                    $js['name'] = $message->text;
                                    $user->db->setColumn('state','addmodel' . json_encode($js));
                                    $bot->sendMessage($user, $l['model_send_limit'],$kb);
                                }
                            } elseif (!isset($js['limit'])) {
                                if (!is_numeric($message->text) || (int)$message->text < 1 || (int)$message->text > 100) {
                                    $bot->sendMessage($user, $l['e_connection_limit'],$kb);
                                } else {
                                    $js['limit'] = (int)$message->text;
                                    $user->db->setColumn('state','addmodel' . json_encode($js));
                                    $bot->sendMessage($user, $l['model_send_url'],$kb);
                                }
                            } elseif (!isset($js['format'])) {
                                if (strlen($message->text) > 150) {
                                    $bot->sendMessage($user,str_replace('&ch',150,$l['e_ch_limit'],$kb));
                                } elseif (stripos($message->text,'{LINKWB}') === false) {
                                    $bot->sendMessage($user,$l['e_weburl'],$kb);
                                } else {
                                    $js['format'] = $message->text;
                                    $user->db->setColumn('state','');
                                    $models = json_decode($user->db->models,true);
                                    $models[] = $js;
                                    $user->db->setColumn('models',json_encode($models));
                                    $bot->sendMessage($user, $l['model_created'],$kb);
                                }
                            }
                        } elseif (stripos($user->db->state,'setwb') === 0) {
                            $ex = explode(' ',$user->db->state);
                            $kb = [
                                [['text' => $l['go_back'], 'callback_data' => '/start']],
                            ];
                            if (!isset($ex[1])) {
                                $tok = $message->text;
                                $botApi = new botApi('bot' . $tok,$config);
                                $getMe = $botApi->getMe();
                                if (!$getMe->ok) {
                                    $bot->sendMessage($user,$l['e_token_not_valid'],$kb);
                                } else {
                                    $user->db->setColumn('state', 'setwb ' . $tok . ' 0');
                                    $models = json_decode($user->db->models,true);
                                    $m = $models[$user->db->model];
                                    $bot->sendMessage($user, $message, str_replace(['&name','&limit','&format'],[$m['name'],$m['limit'],$m['format']],$l['send_url']), $kb);
                                }
                            } elseif (isset($ex[2])) {
                                $tok = $ex[1];
                                $botApi = new botApi('bot' . $tok,$config);
                                $getMe = $botApi->getMe();
                                $wburl = $message->text;
                                $models = json_decode($user->db->models,true);
                                $m = $models[$user->db->model];
                                $vars = ['{LINKWB}','{TOKEN}','{USERBOT}','{BOTID}','{USERID}','{USERNAME}'];
                                $reps = [$wburl,$tok,$getMe->username,$getMe->id,$user->id,$user->username];
                                $wburl = str_replace($vars,$reps,$m['format']);
                                $setwb = $botApi->setWebhook($wburl,$m['limit']);
                                if (!$setwb->ok) {
                                    $bot->sendMessage($user,str_replace('&error',$setwb->description,$l['wb_not_set']),$kb);
                                } else {
                                    $bot->sendMessage($user,$l['wb_set'],$kb);
                                }
                            }

                        }
                    }
                }
            }
        }

    }
}
