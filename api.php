<?php
    include('./simple_html_dom.php');
    include('./mysql_i.php');

    function getTotalData($html, $url, $league_name, $link) {
        @$ajax_url = $html->find("li.stats-menu-table a", 0)->href;
        if(empty($ajax_url)) return;

        $pos1 = strpos($ajax_url, '&ts=');
        $pos2 = strpos($ajax_url, '&dcheck=');
        $token = substr($ajax_url, $pos1+4, $pos2-$pos1-4);
        if(empty($token)) return;

        echo "<br/>===================================<br/>";
        echo "<h4 class='no-inline' style='text-transform:uppercase;'>".$league_name."</h4>";

        //standing overall
        $t_url = $url.'standings/?table=table&table_sub=overall&ts='.$token.'&dcheck=0';
        $flag = 'standings_overall';
        $return_flag = 'standings';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);

        //standing home
        $t_url = $url.'standings/?table=table&table_sub=home&ts='.$token.'&dcheck=0';
        $flag = 'standings_home';
        $return_flag = 'standings';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);

        //standing away
        $t_url = $url.'standings/?table=table&table_sub=away&ts='.$token.'&dcheck=0';
        $flag = 'standings_away';
        $return_flag = 'standings';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);

        //form overall
        $t_url = $url.'standings/?table=form&table_sub=overall&ts='.$token.'&dcheck=0';
        $flag = 'form_overall';
        $return_flag = 'form';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);

        //form home
        $t_url = $url.'standings/?table=form&table_sub=home&ts='.$token.'&dcheck=0';
        $flag = 'form_home';
        $return_flag = 'form';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);

        //form away
        $t_url = $url.'standings/?table=form&table_sub=away&ts='.$token.'&dcheck=0';
        $flag = 'form_away';
        $return_flag = 'form';
        getSubTable($t_url, $token, $flag, $return_flag, $league_name, $link);
    }

    function getSubTable($url, $token, $flag, $return_flag, $league_name, $link) {
        $html = file_get_html($url);

        if(empty($html)) return;

        if($return_flag == 'standings')
            $matches = $html->find('tr.odd, tr.even');
        else
            $matches = $html->find('div.stats-table-container', 0)->find('table tr.odd, table tr.even');

        echo "<br/><h4>Total result :".$flag."</h4>";
        $tmp = array(); $i = 0;
        foreach ($matches as $item) {
            $tmp[$i]['rank'] = (int)$item->find("td.rank", 0)->plaintext;
            $tmp[$i]['team_name'] = $item->find("td.participant_name", 0)->plaintext;
            $tmp[$i]['matches_played'] = $item->find("td.matches_played", 0)->plaintext;
            $tmp[$i]['wins_regular'] = $item->find("td.wins_regular", 0)->plaintext;
            $tmp[$i]['draws'] = $item->find("td.draws", 0)->plaintext;
            $tmp[$i]['losses_regular'] = $item->find("td.losses_regular", 0)->plaintext;
            $t_goals = explode(":", $item->find("td.goals", 0)->plaintext);
            $tmp[$i]['goals_w'] = $t_goals[0];
            $tmp[$i]['goals_l'] = $t_goals[1];
            $tmp[$i]['points'] = $item->find("td.points", 0)->plaintext;

            $result = $item->find("td.form div.matches-5 a");

            $prevs = array();
            foreach ($result as $sitem) {
                $sitem_class = $sitem->class;

                if(strpos($sitem_class, "form-w") > 0) $prevs[] = 'w';
                elseif(strpos($sitem_class, "form-l") > 0) $prevs[] = 'l';
                elseif(strpos($sitem_class, "form-d") > 0) $prevs[] = 'd';
            }
            $tmp[$i]['prev_matches'] = $prevs;
            
            $i++;
        }
        
        $sql = "DELETE FROM total_matches WHERE league_name = '".trim($league_name)."' AND kind_flag = '".trim($flag)."';";
        foreach ($tmp as $item) {
            if(empty($item) || count($item['prev_matches']) < 5) continue;
            echo "<br/>".$item['rank']." ".$item['team_name']." ".$item['matches_played']." ".$item['wins_regular']." ".$item['draws']." ".$item['losses_regular']." ".$item['goals_w'].":".$item['goals_l']." ".$item['points']." ".$item['prev_matches'][0]." ".$item['prev_matches'][1]." ".$item['prev_matches'][2]." ".$item['prev_matches'][3]." ".$item['prev_matches'][4];

            $sql .= "INSERT INTO total_matches(league_name, kind_flag, rank, team_name, matches_played, wins_regular, draws, losses_regular, goals_w, goals_l, points, odds_1, odds_2, odds_3, odds_4, odds_5) 
                        VALUES('".trim($league_name)."', '".trim($flag)."', ".$item['rank'].", '".trim($item['team_name'])."', ".$item['matches_played'].", ".$item['wins_regular'].", ".$item['draws'].", ".$item['losses_regular'].", ".$item['goals_w'].", ".$item['goals_l'].", ".$item['points'].", '".$item['prev_matches'][0]."', '".$item['prev_matches'][1]."', '".$item['prev_matches'][2]."', '".$item['prev_matches'][3]."', '".$item['prev_matches'][4]."');";
        }
        multi_query($link, $sql);
    }

    function getNextOdds($url, $current_date, $league_name, $link) {
        $html = file_get_html($url);
 
        if(empty($html)) return array();

        getTotalData($html, $url, $league_name, $link);

        $odds_1 = $html->find("table.table-main--leaguefixtures tr td[title='Finished']");
        $odds_2 = $html->find("table.table-main--leaguefixtures tr td[colspan='2'], table.table-main--leaguefixtures tr td.table-main__result");
        $odds_link = $html->find("table.table-main--leaguefixtures tr td a.in-match");

        $odds_arr = array();
        $odds_link_arr = array();
        foreach($odds_1 as $item)
            array_push($odds_arr, "");
        
        $duplicate = count($odds_1); $i = 0;
        foreach($odds_2 as $item) {
            if($duplicate > $i) {$i++; continue;}
            array_push($odds_arr, $item->plaintext);
        }

        foreach($odds_link as $item)
            $odds_link_arr[] = $item->href;

        $result = array();
        foreach($odds_arr as $key => $item) {
            if(empty($item)) continue;

            if(strtoupper(substr($item, 0, 1)) == "T") {
                $result[] = $odds_link_arr[$key];
            }// } else {
            //     $year = date("Y");
            //     $match_date = DateTime::createFromFormat('d.m.Y', explode(" ", $item)[0].$year);
            //     $interval = (int)date_diff($current_date, $match_date)->format('%a');
                
            //     if($interval == 1) $result[] = $odds_link_arr[$key];
            // }
        }

        return $result;
    }

    function getBtsValue($base_url, $url, $link, $match_id) {
        echo '<br/><h4>BTS</h4>';
        $ajax_odds_bts = getAjaxData($url, 'odds'); 

        $value_yes = 0;
        $value_no = 0;
        if(empty($ajax_odds_bts)){
            echo $value_yes." : ".$value_no;
        } else {
            // get yes info
            $pos = strpos($ajax_odds_bts, 'data-bid="16"');
            $pos_start = strpos($ajax_odds_bts, 'class="table-main__detail-odds', $pos);
            $pos_end = strpos($ajax_odds_bts, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_bts, 'onclick="load_odds_archive', $pos_start);
            
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_bts, "', 16", $pos_start);
                $url_token = substr($ajax_odds_bts, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_yes = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_1 = false;
            }

            // get no info
            $pos_start = strpos($ajax_odds_bts, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_bts, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_bts, 'onclick="load_odds_archive', $pos_start);
            $flag_2 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_bts, "', 16", $pos_start);
                $url_token = substr($ajax_odds_bts, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_no = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_2 = false;
            }

            if(!$flag_1 || !$flag_2) {
                $pos = strpos($ajax_odds_bts, 'data-bid="429"');
                $pos_start = strpos($ajax_odds_bts, 'class="table-main__detail-odds', $pos);
                $pos_end = strpos($ajax_odds_bts, '</td>', $pos_start);

                if(!$flag_1) {
                    $pos_main = strpos($ajax_odds_bts, 'onclick="load_odds_archive', $pos_start);

                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_bts, "', 429", $pos_start);
                        $url_token = substr($ajax_odds_bts, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/429/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_yes = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_bts, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_bts, '</td>', $pos_start);

                if(!$flag_2) {
                    $pos_main = strpos($ajax_odds_bts, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_bts, "', 429", $pos_start);
                        $url_token = substr($ajax_odds_bts, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/429/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_no = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }
            }
        }

        $sql = "DELETE FROM bts WHERE match_id = ".$match_id.";
                    INSERT INTO bts(match_id, odds_yes, odds_no) 
                        VALUES(".$match_id.", ".$value_yes.", ".$value_no.");";
            multi_query($link, $sql);
        echo $value_yes." : ".$value_no;
    }

    function get1X2Value($base_url, $url, $link, $match_id) {
        echo '<br/><h4>1 X 2</h4>';
        $ajax_odds_1X2 = getAjaxData($url, 'odds'); 
      
        if(empty($ajax_odds_1X2)){
            $value_1 = "-";
            $value_x = "-";
            $value_2 = "-";
            echo $value_1." : ".$value_x." : ".$value_2;
        } else {
            $pos = strpos($ajax_odds_1X2, 'id="match-add-to-selection"');
            $pos = strpos($ajax_odds_1X2, 'data-odd="', $pos);
            $value_1 = (float)substr($ajax_odds_1X2, $pos+10, 4);
            $pos = $pos + 13;
            $pos = strpos($ajax_odds_1X2, 'data-odd="', $pos);
            $value_x = (float)substr($ajax_odds_1X2, $pos+10, 4);
            $pos = $pos + 13;
            $pos = strpos($ajax_odds_1X2, 'data-odd="', $pos);
            $value_2 = (float)substr($ajax_odds_1X2, $pos+10, 4);
            $pos = $pos + 13;
            
            $sql = "DELETE FROM one_two WHERE match_id = ".$match_id.";
                    INSERT INTO one_two(match_id, odds_1, odds_x, odds_2) 
                        VALUES(".$match_id.", ".$value_1.", ".$value_x.", ".$value_2.");";
            multi_query($link, $sql);
            echo $value_1." : ".$value_x." : ".$value_2;

            echo '<br/><h4>1 X 2(bet365)</h4>';
            //get bet365 info
            $value_365_1 = 0;
            $value_365_X = 0;
            $value_365_2 = 0;

            // get 1 info
            $pos = strpos($ajax_odds_1X2, 'data-bid="16"');
            $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos);
            $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);
            $flag_1 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_1X2, "', 16", $pos_start);
                $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_1 = false;
            }

            // get X info
            $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);
            $flag_X = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_1X2, "', 16", $pos_start);
                $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_365_X =(empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_X = false;
            }
            
            // get 2 info
            $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);
            $flag_2 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_1X2, "', 16", $pos_start);
                $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_2 = false;
            }
            
            if(!$flag_1 || !$flag_X || !$flag_2) {
                $pos = strpos($ajax_odds_1X2, 'data-bid="32"');
                $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos);
                $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

                if(!$flag_1) {
                    $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);

                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_1X2, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

                if(!$flag_X) {
                    $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_1X2, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_365_X = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_1X2, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_1X2, '</td>', $pos_start);

                if(!$flag_2) {
                    $pos_main = strpos($ajax_odds_1X2, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_1X2, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_1X2, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }
            }

            $sql = "DELETE FROM one_two_365 WHERE match_id = ".$match_id.";
                    INSERT INTO one_two_365(match_id, odds_1, odds_x, odds_2) 
                        VALUES(".$match_id.", ".$value_365_1.", ".$value_365_X.", ".$value_365_2.");";
            multi_query($link, $sql);
            echo $value_365_1." : ".$value_365_X." : ".$value_365_2;
        }
    }

    function getOUValue($base_url, $url, $link, $match_id) {
        echo '<br/><h4>OU</h4>';
        $ajax_odds_OU = getAjaxData($url, 'odds'); 
       
        echo '<h4>1.5</h4>';
        //get 1.5 data
        if(empty($ajax_odds_OU)) {
            $value_15_1 = "-";
            $value_15_2 = "-";
            echo $value_15_1." : ".$value_15_2;
        } else {
            $pos = strpos($ajax_odds_OU, 'table-main__doubleparameter">1.5');
            $pos_t = $pos;
            $pos = strpos($ajax_odds_OU, 'id="match-add-to-selection"', $pos);
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_15_1 = (float)substr($ajax_odds_OU, $pos+10, 4);
            $pos = $pos + 13;
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_15_2 = (float)substr($ajax_odds_OU, $pos+10, 4);
            
            $sql = "DELETE FROM ou WHERE match_id = ".$match_id." AND rate_name = 1.5;
                    INSERT INTO ou(rate_name, match_id, odds_1, odds_2) VALUES(1.5, ".$match_id.", ".$value_15_1.", ".$value_15_2.");";
            multi_query($link, $sql);
            echo $value_15_1." : ".$value_15_2;

            echo '<br/><h4>OU1.5(bet365)</h4>';
            //get OU1.5 info
            $value_15_bet365_1 = 0;
            $value_15_bet365_2 = 0;

            // get 1 info
            $pos = strpos($ajax_odds_OU, 'data-bid="16"', $pos_t);
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_1 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_15_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_1 = false;
            }

            // get 2 info
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_2 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_15_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_2 = false;
            }
            
            if(!$flag_1 || !$flag_2) {
                $pos = strpos($ajax_odds_OU, 'data-bid="32"', $pos_t);
                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_1) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);

                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_15_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_2) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_15_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }
            }

            $sql = "DELETE FROM ou_365 WHERE match_id = ".$match_id." AND rate_name = 1.5;
                    INSERT INTO ou_365(rate_name, match_id, odds_1, odds_2) VALUES(1.5, ".$match_id.", ".$value_15_bet365_1.", ".$value_15_bet365_2.");";
            multi_query($link, $sql);
            echo $value_15_bet365_1." : ".$value_15_bet365_2;
        }

        //get 2.5 data
        echo '<br/><h4>2.5</h4>';
        if(empty($ajax_odds_OU)) {
            $value_25_1 = "-";
            $value_25_2 = "-";
            echo $value_25_1." : ".$value_25_2;
        } else {
            $pos = strpos($ajax_odds_OU, 'table-main__doubleparameter">2.5');
            $pos_t = $pos;
            $pos = strpos($ajax_odds_OU, 'id="match-add-to-selection"', $pos);
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_25_1 = (float)substr($ajax_odds_OU, $pos+10, 4);
            $pos = $pos + 13;
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_25_2 = (float)substr($ajax_odds_OU, $pos+10, 4);
            
            $sql = "DELETE FROM ou WHERE match_id = ".$match_id." AND rate_name = 2.5;
                    INSERT INTO ou(rate_name, match_id, odds_1, odds_2) VALUES(2.5, ".$match_id.", ".$value_25_1.", ".$value_25_2.");";
            multi_query($link, $sql);
            echo $value_25_1." : ".$value_25_2;

            echo '<br/><h4>OU2.5(bet365)</h4>';
            //get OU2.5 info
            $value_25_bet365_1 = 0;
            $value_25_bet365_2 = 0;

            // get 1 info
            $pos = strpos($ajax_odds_OU, 'data-bid="16"', $pos_t);
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_1 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_25_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_1 = false;
            }

            // get 2 info
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_2 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_25_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_2 = false;
            }
            
            if(!$flag_1 || !$flag_2) {
                $pos = strpos($ajax_odds_OU, 'data-bid="32"', $pos_t);
                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_1) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);

                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_25_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_2) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_25_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }
            }

            $sql = "DELETE FROM ou_365 WHERE match_id = ".$match_id." AND rate_name = 2.5;
                    INSERT INTO ou_365(rate_name, match_id, odds_1, odds_2) VALUES(2.5, ".$match_id.", ".$value_25_bet365_1.", ".$value_25_bet365_2.");";
            multi_query($link, $sql);
            echo $value_25_bet365_1." : ".$value_25_bet365_2;
        }

        //get 3.5 data
        echo '<br/><h4>3.5</h4>';
        if(empty($ajax_odds_OU)) {
            $value_35_1 = "-";
            $value_35_2 = "-";
            echo $value_35_1." : ".$value_35_2;
        } else {
            $pos = strpos($ajax_odds_OU, 'table-main__doubleparameter">3.5');
            $pos_t = $pos;
            $pos = strpos($ajax_odds_OU, 'id="match-add-to-selection"', $pos);
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_35_1 = (float)substr($ajax_odds_OU, $pos+10, 4);
            $pos = $pos + 13;
            $pos = strpos($ajax_odds_OU, 'data-odd="', $pos);
            $value_35_2 = (float)substr($ajax_odds_OU, $pos+10, 4);
            
            $sql = "DELETE FROM ou WHERE match_id = ".$match_id." AND rate_name = 3.5;
                    INSERT INTO ou(rate_name, match_id, odds_1, odds_2) VALUES(3.5, ".$match_id.", ".$value_35_1.", ".$value_35_2.");";
            multi_query($link, $sql);
            echo $value_35_1." : ".$value_35_2;

            echo '<br/><h4>OU3.5(bet365)</h4>';
            //get OU3.5 info
            $value_35_bet365_1 = 0;
            $value_35_bet365_2 = 0;

            // get 1 info
            $pos = strpos($ajax_odds_OU, 'data-bid="16"', $pos_t);
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_1 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_35_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_1 = false;
            }

            // get 2 info
            $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
            $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

            $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
            $flag_2 = true;
            if($pos_main > $pos_start && $pos_main < $pos_end) {
                $pos_t = strpos($ajax_odds_OU, "', 16", $pos_start);
                $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                $t_url = $base_url.'/archive-odds/'.$url_token.'/16/';
                
                $t_data = getAjaxData($t_url, '');
                $value_35_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
            } else {
                $flag_2 = false;
            }
            
            if(!$flag_1 || !$flag_2) {
                $pos = strpos($ajax_odds_OU, 'data-bid="32"', $pos_t);
                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_1) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);

                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_35_bet365_1 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }

                $pos_start = strpos($ajax_odds_OU, 'class="table-main__detail-odds', $pos_end);
                $pos_end = strpos($ajax_odds_OU, '</td>', $pos_start);

                if(!$flag_2) {
                    $pos_main = strpos($ajax_odds_OU, 'onclick="load_odds_archive', $pos_start);
                    
                    if($pos_main > $pos_start && $pos_main < $pos_end) {
                        $pos_t = strpos($ajax_odds_OU, "', 32", $pos_start);
                        $url_token = substr($ajax_odds_OU, $pos_main+34, $pos_t -($pos_main+34));
                        $t_url = $base_url.'/archive-odds/'.$url_token.'/32/';
                        
                        $t_data = getAjaxData($t_url, '');
                        $value_35_bet365_2 = (empty($t_data) ? 0 : $t_data[count($t_data)-1]->odd);
                    }
                }
            }

            $sql = "DELETE FROM ou_365 WHERE match_id = ".$match_id." AND rate_name = 3.5;
                    INSERT INTO ou_365(rate_name, match_id, odds_1, odds_2) VALUES(3.5, ".$match_id.", ".$value_35_bet365_1.", ".$value_35_bet365_2.");";
            multi_query($link, $sql);
            echo $value_35_bet365_1." : ".$value_35_bet365_2;
        }
    }

    function getLastAwayOdds($main_url, $match_teams, $last_odds_token, $link, $match_id) {
        echo '<br/><h4 class="no-inline">Away odds</h4>';

        $away_url = 'https://www.betexplorer.com/soccer/'.$main_url.'/standings/?table=table&table_sub=away&ts='.$last_odds_token.'&dcheck=0';
        $ajax_order = getAjaxAllData($away_url);

        if(empty($ajax_order)) {
            $value_1_1 = "-";
            $value_1_2 = "-";
            $value_1_3 = "-";
            $value_1_4 = "-";
            $value_1_5 = "-";
            $value_2_1 = "-";
            $value_2_2 = "-";
            $value_2_3 = "-";
            $value_2_4 = "-";
            $value_2_5 = "-";
            echo $match_teams[0]." : ".$value_1_1." ".$value_1_2." ".$value_1_3." ".$value_1_4." ".$value_1_5."<br/>";
            echo $match_teams[1]." : ".$value_2_1." ".$value_2_2." ".$value_2_3." ".$value_2_4." ".$value_2_5."<br/>";
        } else {
            $pos = strpos($ajax_order, '>'.$match_teams[0].'<');

            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_0 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_1 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_2 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_3 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_4 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_5 = substr($ajax_order, $pos+33, 1);

            echo $match_teams[0]." : ".$value_1_1." ".$value_1_2." ".$value_1_3." ".$value_1_4." ".$value_1_5."<br/>";
       
            $pos = strpos($ajax_order, '>'.$match_teams[1].'<');

            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_0 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_1 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_2 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_3 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_4 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_5 = substr($ajax_order, $pos+33, 1);
    
            echo $match_teams[1]." : ".$value_2_1." ".$value_2_2." ".$value_2_3." ".$value_2_4." ".$value_2_5."<br/>";
            $sql = "DELETE FROM away_matches WHERE match_id = ".$match_id.";
                    INSERT INTO away_matches(team_name, match_id, odds_1, odds_2, odds_3, odds_4, odds_5)
                     VALUES('".$match_teams[0]."', ".$match_id.", '".$value_1_1."', '".$value_1_2."', '".$value_1_3."', '".$value_1_4."', '".$value_1_5."'),
                            ('".$match_teams[1]."', ".$match_id.", '".$value_2_1."', '".$value_2_2."', '".$value_2_3."', '".$value_2_4."', '".$value_2_5."');";
            multi_query($link, $sql);
        }
    }

    function getLastHomeOdds($main_url, $match_teams, $last_odds_token, $link, $match_id) {
        echo '<br/><h4 class="no-inline">Home odds</h4>';

        $home_url = 'https://www.betexplorer.com/soccer/'.$main_url.'/standings/?table=table&table_sub=home&ts='.$last_odds_token.'&dcheck=0';
        $ajax_order = getAjaxAllData($home_url);

        if(empty($ajax_order)) {
            $value_1_1 = "-";
            $value_1_2 = "-";
            $value_1_3 = "-";
            $value_1_4 = "-";
            $value_1_5 = "-";
            $value_2_1 = "-";
            $value_2_2 = "-";
            $value_2_3 = "-";
            $value_2_4 = "-";
            $value_2_5 = "-";
            echo $match_teams[0]." : ".$value_1_1." ".$value_1_2." ".$value_1_3." ".$value_1_4." ".$value_1_5."<br/>";
            echo $match_teams[1]." : ".$value_2_1." ".$value_2_2." ".$value_2_3." ".$value_2_4." ".$value_2_5."<br/>";
        } else {
            $pos = strpos($ajax_order, '>'.$match_teams[0].'<');

            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_0 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_1 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_2 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_3 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_4 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_1_5 = substr($ajax_order, $pos+33, 1);

            echo $match_teams[0]." : ".$value_1_1." ".$value_1_2." ".$value_1_3." ".$value_1_4." ".$value_1_5."<br/>";

            $pos = strpos($ajax_order, '>'.$match_teams[1].'<');

            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_0 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_1 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_2 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_3 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_4 = substr($ajax_order, $pos+20, 1);
            $pos = $pos + 21;
            $pos = strpos($ajax_order, 'class="form-bg form-', $pos);
            $value_2_5 = substr($ajax_order, $pos+33, 1);
    
            echo $match_teams[1]." : ".$value_2_1." ".$value_2_2." ".$value_2_3." ".$value_2_4." ".$value_2_5."<br/>";
            $sql = "DELETE FROM home_matches WHERE match_id = ".$match_id.";
                    INSERT INTO home_matches(team_name, match_id, odds_1, odds_2, odds_3, odds_4, odds_5)
                     VALUES('".$match_teams[0]."', ".$match_id.", '".$value_1_1."', '".$value_1_2."', '".$value_1_3."', '".$value_1_4."', '".$value_1_5."'),
                            ('".$match_teams[1]."', ".$match_id.", '".$value_2_1."', '".$value_2_2."', '".$value_2_3."', '".$value_2_4."', '".$value_2_5."');";
            multi_query($link, $sql);
        }
    }

    function getLastMatchesInfo($main_url, $match_teams, $last_odds_token, $link, $match_id) {
        echo '<br/><h4 class="no-inline">Last 5 Matches</h4>';

        $home_url = 'https://www.betexplorer.com/soccer/'.$main_url.'/standings/?table=table&table_sub=overall&ts='.$last_odds_token.'&dcheck=0';
    
        $ajax_match = getAjaxAllData($home_url);

        $grade1 = 0; $mp1 = 0; $w1=0; $d1=0; $l1=0; $g11=0; $g21=0; $pts1=0;
        $grade2 = 0; $mp2 = 0; $w2=0; $d2=0; $l2=0; $g12=0; $g22=0; $pts2=0;
        if(!empty($ajax_match)) {
            $t_team = '/soccer/team/'.strtolower(str_replace(".", "", str_replace(" ", "-", $match_teams[0]))).'/';
            $pos = strpos($ajax_match, $t_team);

            $pos_prev = $pos-450;
            $pos_prev = strpos($ajax_match, 'class="rank col_rank', $pos_prev);
            $pos_prev = strpos($ajax_match, '">', $pos_prev);
            $pos_end = strpos($ajax_match, '.</td>', $pos_prev);
            $grade1 = substr($ajax_match, $pos_prev+2, $pos_end-$pos_prev-2);

            $pos = strpos($ajax_match, 'col_matches_played', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $mp1 = substr($ajax_match, $pos+20, $pos_end-$pos-20);

            $pos = strpos($ajax_match, 'col_wins_regular', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $w1 = substr($ajax_match, $pos+18, $pos_end-$pos-18);

            $pos = strpos($ajax_match, 'col_draws', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $d1 = substr($ajax_match, $pos+11, $pos_end-$pos-11);

            $pos = strpos($ajax_match, 'col_losses_regular', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $l1 = substr($ajax_match, $pos+20, $pos_end-$pos-20);

            $pos = strpos($ajax_match, 'col_goals', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $g = explode(":", substr($ajax_match, $pos+11, $pos_end-$pos-11));
            $g11 = $g[0]; $g21 = $g[1];

            $pos = strpos($ajax_match, 'col_points', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $pts1 = substr($ajax_match, $pos+12, $pos_end-$pos-12);
            
            echo $match_teams[0].' : '.$grade1.' : '.$mp1.' : '.$w1." : ".$d1." : ".$l1." : ".$g11." : ".$g21." : ".$pts1;
            echo "<br/>";

            $t_team = '/soccer/team/'.strtolower(str_replace(".", "", str_replace(" ", "-", $match_teams[1]))).'/';
            $pos = strpos($ajax_match, $t_team);

            $pos_prev = $pos-450;
            $pos_prev = strpos($ajax_match, 'class="rank col_rank', $pos_prev);
            $pos_prev = strpos($ajax_match, '">', $pos_prev);
            $pos_end = strpos($ajax_match, '.</td>', $pos_prev);
            $grade2 = substr($ajax_match, $pos_prev+2, $pos_end-$pos_prev-2);

            $pos = strpos($ajax_match, 'col_matches_played', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $mp2 = substr($ajax_match, $pos+20, $pos_end-$pos-20);

            $pos = strpos($ajax_match, 'col_wins_regular', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $w2 = substr($ajax_match, $pos+18, $pos_end-$pos-18);

            $pos = strpos($ajax_match, 'col_draws', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $d2 = substr($ajax_match, $pos+11, $pos_end-$pos-11);

            $pos = strpos($ajax_match, 'col_losses_regular', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $l2 = substr($ajax_match, $pos+20, $pos_end-$pos-20);

            $pos = strpos($ajax_match, 'col_goals', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $g = explode(":", substr($ajax_match, $pos+11, $pos_end-$pos-11));
            $g12 = $g[0]; $g22 = $g[1];

            $pos = strpos($ajax_match, 'col_points', $pos);
            $pos_end = strpos($ajax_match, '</td>', $pos);
            $pts2 = substr($ajax_match, $pos+12, $pos_end-$pos-12);
            
            echo $match_teams[1].' : '.$grade2.' : '.$mp2.' : '.$w2." : ".$d2." : ".$l2." : ".$g12." : ".$g22." : ".$pts2;

            $sql = "DELETE FROM overall_matches WHERE match_id = ".$match_id.";
                    INSERT INTO overall_matches(team_name, match_id, grade, mp, w, d, l, g1, g2, pts)
                     VALUES('".$match_teams[0]."', ".$match_id.", ".$grade1.", ".$mp1.", ".$w1.", ".$d1.", ".$l1.", ".$g11.", ".$g21.", ".$pts1."),
                            ('".$match_teams[1]."', ".$match_id.", ".$grade2.", ".$mp2.", ".$w2.", ".$d2.", ".$l2.", ".$g12.", ".$g22.", ".$pts2.");";
            multi_query($link, $sql);
        }
    }

    function get10Matches($html, $link, $match_id) {
        $tables = $html->find(".box-overflow__in table.table-main.h-mb15");
        $cnt = 0; $sql = "";
        foreach($tables as $item) {
            //get common Info
            echo '<br/><h4 class="no-inline">10 Matches</h4>';
            
            if($cnt == 0) {
                $sql = "DELETE FROM tone_ten_matches WHERE match_id = ".$match_id.";
                        INSERT INTO tone_ten_matches (match_id, odds, team1, team2, result, rate1, rate2, rate3, match_date)
                        VALUE ";
            } else {
                $sql = "DELETE FROM ttwo_ten_matches WHERE match_id = ".$match_id.";
                        INSERT INTO ttwo_ten_matches (match_id, odds, team1, team2, result, rate1, rate2, rate3, match_date)
                        VALUE ";
            }

            $trs = $item->find("tr");
            foreach($trs as $tr) {
                $tds_arr = array();
                $tds = $tr->find("td");
                foreach($tds as $td_key => $td) {
                    switch($td_key) {
                        case 0: $tds_arr[] = substr($odds = $td->find("i", 0)->class, 11, 1); break;
                        case 1:
                        case 2:
                        case 3: $tds_arr[] = $td->plaintext; break;
                        case 4:
                        case 5:
                        case 6:  
                            if($td->class == "table-main__odds" && isset($td->{'data-odd'}))
                                $tds_arr[] = $td->{'data-odd'};
                            elseif($td->class != "table-main__odds" 
                                    && isset($td->find("span span span", 0)->{'data-odd'}))
                                $tds_arr[] = $td->find("span span span", 0)->{'data-odd'};
                            else
                                $tds_arr[] = "";
                            break;
                        case 7: $tds_arr[] = $td->plaintext; break;
                        case 8: $tds_arr[] = DateTime::createFromFormat('d.m.Y', $td->plaintext)->format('Y-m-d'); break;
                    }
                }
              
                $sql .= "(".$match_id.",'".$tds_arr[0]."','".$tds_arr[1]."','".$tds_arr[2]."','".$tds_arr[3]."',".$tds_arr[4].",".$tds_arr[5].",".$tds_arr[6].",'".$tds_arr[8]."'),";
                echo $tds_arr[0]." ".$tds_arr[1]." ".$tds_arr[2]." ".$tds_arr[3]." ".$tds_arr[4]." ".$tds_arr[5]." ".
                         $tds_arr[6]." ".$tds_arr[7]." ".$tds_arr[8]."<br/>";
            }

            $sql = substr($sql, 0, -1).";";
            multi_query($link, $sql);
            $cnt ++;
        }
    }

    function getOddsData($base_url, $child_url, $main_url, $link) {
        $url = $base_url.$child_url;
        $html = file_get_html($url);

        if(empty($html)) return;

        $last_odds_token_tmp = $html->find("li.stats-menu-overall a", 0)->href;
        $tmp_pos1 = strpos($last_odds_token_tmp, "&ts=");
        $tmp_pos2 = strpos($last_odds_token_tmp, "&dcheck=");
        $last_odds_token = substr($last_odds_token_tmp, ($tmp_pos1+4), ($tmp_pos2-$tmp_pos1-4));

        // get match time
        echo '<h4>Match Time</h4>';
        $match_time_tmp = $html->find("li.list-details__item #match-date", 0)->{'data-dt'};
        $match_time = DateTime::createFromFormat('d,m,Y,H,i', $match_time_tmp)->format('Y-m-d H:i');
        echo $match_time;

        // get match teams
        echo '<br/><h4>Match Teams</h4>';
        $match_team_objs = $html->find("h2.list-details__item__title a");
        $match_teams = array();
        foreach($match_team_objs as $item) {
            $match_teams[] = $item->plaintext;
        }
        echo $match_teams[0]." : ".$match_teams[1];

        $sql = 'select IF(COUNT(*) = 0, 0, id) AS id
                FROM matches
                WHERE lieg_name = "'.$main_url.'"
                AND team1 = "'.$match_teams[0].'" 
                AND match_time = "'.$match_time.':00";';
        $match_id = query($link, $sql)->row['id'];

        if((int)$match_id > 0) { //when update
            $sql = "UPDATE matches
                    SET lieg_name='".$main_url."', match_time='".$match_time."', team1='".$match_teams[0]."', team2='".$match_teams[1]."'
                    WHERE id=".$match_id.";";
            query($link, $sql);
        } else { //when insert
            $sql = "SET @id = (SELECT IF(MAX(id) IS NULL, 1, MAX(id)+1) AS id FROM matches);
                    INSERT INTO matches(id, lieg_name, match_time, team1, team2) 
                        VALUES(@id, '".$main_url."', '".$match_time."', '".$match_teams[0]."', '".$match_teams[1]."');
                    SELECT @id AS id;";
            $match_id = multi_query($link, $sql)->row['id'];    
        }

        // get bet rate
        $url_piece = explode("/", $child_url);
        $url_piece_unit = $url_piece[count($url_piece)-2];
        $url_bts = $base_url.'/match-odds/'.$url_piece_unit.'/0/bts/';
        getBtsValue($base_url, $url_bts, $link, $match_id);
        $url_1X2 = $base_url.'/match-odds/'.$url_piece_unit.'/0/1x2/';
        get1X2Value($base_url, $url_1X2, $link, $match_id);
        $url_ou = $base_url.'/match-odds/'.$url_piece_unit.'/0/ou/';
        getOUValue($base_url, $url_ou, $link, $match_id);

        //get last 5 odds
        getLastHomeOdds($main_url, $match_teams, $last_odds_token, $link, $match_id);
        getLastAwayOdds($main_url, $match_teams, $last_odds_token, $link, $match_id);
        getLastMatchesInfo($main_url, $match_teams, $last_odds_token, $link, $match_id);

        //get 10 matches
        get10Matches($html, $link, $match_id);
    }

    function getAjaxData($url, $key) {
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        curl_setopt($curl_handle, CURLOPT_REFERER, "https://www.betexplorer.com/");
        $contents = curl_exec($curl_handle);
        curl_close($curl_handle);

        if(!empty($contents)) {
            if(!empty($key))
                return json_decode($contents)->$key;
            else
                return json_decode($contents);
        }
        else
            return null;
    }

    function getAjaxAllData($url) {
        $curl_handle=curl_init();
        curl_setopt($curl_handle, CURLOPT_URL, $url);
        curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_USERAGENT, 'Your application name');
        curl_setopt($curl_handle, CURLOPT_REFERER, "https://www.betexplorer.com/");
        $contents = curl_exec($curl_handle);
        curl_close($curl_handle);

        return $contents;
    }

    function main($base_url, $odds_url, $main_url, $link) {
        $current_date = new DateTime("now");
        $next_odds_links = getNextOdds($odds_url, $current_date, $main_url, $link);

        foreach($next_odds_links as $item) {
            getOddsData($base_url, $item, $main_url, $link);
        }
          
    }

    $base_url = $_REQUEST['base_url'];
    $odds_url = $_REQUEST['odds_url'];
    $main_url = $_REQUEST['main_url'];
    
    $host = "localhost";
    $user = "root";
    $password = "";
    $db = "betexplorer";
    $link = sqlConnect($host, $user, $password, $db);
    
    main($base_url, $odds_url, $main_url, $link);
?>