var load_cnt = 0;
            var base_url = 'https://www.betexplorer.com';
            var odds_urls = [
                'https://www.betexplorer.com/soccer/italy/serie-a/',
                'https://www.betexplorer.com/soccer/italy/serie-b/',
                'https://www.betexplorer.com/soccer/italy/serie-c-group-a/',
                'https://www.betexplorer.com/soccer/italy/serie-c-group-b/',
                'https://www.betexplorer.com/soccer/italy/serie-c-group-c/',
				'https://www.betexplorer.com/soccer/italy/coppa-italia/',
				'https://www.betexplorer.com/soccer/italy/coppa-italia-serie-c/',
				'https://www.betexplorer.com/soccer/italy/coppa-italia-serie-d/',
				'https://www.betexplorer.com/soccer/israel/ligat-ha-al/',
				'https://www.betexplorer.com/soccer/israel/leumit-league/',
				'https://www.betexplorer.com/soccer/argentina/superliga/',
                'https://www.betexplorer.com/soccer/australia/a-league/',
                'https://www.betexplorer.com/soccer/austria/tipico-bundesliga/',
                'https://www.betexplorer.com/soccer/belgium/jupiler-league/',
                'https://www.betexplorer.com/soccer/brazil/serie-a/',
                'https://www.betexplorer.com/soccer/bulgaria/parva-liga/',
                'https://www.betexplorer.com/soccer/china/super-league/',
                'https://www.betexplorer.com/soccer/croatia/1-hnl/',
                'https://www.betexplorer.com/soccer/czech-republic/1-liga/',
                'https://www.betexplorer.com/soccer/denmark/superliga/',
                'https://www.betexplorer.com/soccer/egypt/premier-league/',
                'https://www.betexplorer.com/soccer/united-arab-emirates/uae-league/',
                'https://www.betexplorer.com/soccer/england/premier-league/',
                'https://www.betexplorer.com/soccer/england/championship/',
                'https://www.betexplorer.com/soccer/england/league-one/',
                'https://www.betexplorer.com/soccer/england/league-two/',
                'https://www.betexplorer.com/soccer/estonia/meistriliiga/',
                'https://www.betexplorer.com/soccer/finland/veikkausliiga/',
                'https://www.betexplorer.com/soccer/france/ligue-1/',
                'https://www.betexplorer.com/soccer/france/ligue-2/',
                'https://www.betexplorer.com/soccer/georgia/erovnuli-liga/',
                'https://www.betexplorer.com/soccer/germany/bundesliga/',
                'https://www.betexplorer.com/soccer/germany/2-bundesliga/',
                'https://www.betexplorer.com/soccer/greece/super-league/',
                'https://www.betexplorer.com/soccer/hungary/otp-bank-liga/',
                'https://www.betexplorer.com/soccer/ireland/premier-division/',
                'https://www.betexplorer.com/soccer/kuwait/premier-league/',
                'https://www.betexplorer.com/soccer/lithuania/a-lyga/',
                'https://www.betexplorer.com/soccer/netherlands/eredivisie/',
                'https://www.betexplorer.com/soccer/netherlands/eerste-divisie/',
                'https://www.betexplorer.com/soccer/northern-ireland/nifl-premiership/',
                'https://www.betexplorer.com/soccer/norway/eliteserien/',
                'https://www.betexplorer.com/soccer/new-zealand/football-championship/',
                'https://www.betexplorer.com/soccer/poland/ekstraklasa/',
                'https://www.betexplorer.com/soccer/portugal/primeira-liga/',
                'https://www.betexplorer.com/soccer/romania/liga-1/',
                'https://www.betexplorer.com/soccer/russia/premier-league/',
                'https://www.betexplorer.com/soccer/saudi-arabia/saudi-professional-league/',
                'https://www.betexplorer.com/soccer/scotland/premiership/',
                'https://www.betexplorer.com/soccer/south-africa/premier-league/',
                'https://www.betexplorer.com/soccer/spain/laliga/',
                'https://www.betexplorer.com/soccer/spain/laliga2/',
                'https://www.betexplorer.com/soccer/sweden/superettan/',
                'https://www.betexplorer.com/soccer/sweden/allsvenskan/',
                'https://www.betexplorer.com/soccer/switzerland/super-league/',
                'https://www.betexplorer.com/soccer/tunisia/ligue-professionnelle-1/',
                'https://www.betexplorer.com/soccer/turkey/super-lig/',
                'https://www.betexplorer.com/soccer/ukraine/premier-league/',
                'https://www.betexplorer.com/soccer/wales/cymru-premier/',
                'https://www.betexplorer.com/soccer/usa/mls/',
                'https://www.betexplorer.com/soccer/iceland/pepsideild/',
                'https://www.betexplorer.com/soccer/japan/j-league/',
                'https://www.betexplorer.com/soccer/portugal/segunda-liga/',
                'https://www.betexplorer.com/soccer/qatar/premier-league/',
                'https://www.betexplorer.com/soccer/uruguay/primera-division/',
            ];
            var main_urls = []; var i=0; var tmp;
            for(i=0; i<odds_urls.length; i++) {
                tmp = odds_urls[i].split("/");
                main_urls.push(tmp[4]+"/"+tmp[5]);
            }

            var flag = false;
            var load_cnt = 0;
            var error_cnt = 0;
            const error_total = 150;
            // getData();
            setInterval(function(){
                console.log(error_cnt);
                error_cnt++;
                if(error_cnt > error_total) {flag = false; load_cnt = (load_cnt + 1) % odds_urls.length;};
                if(!flag) getData(); 
            }, 3000);

            function getData() {
                flag = true;
                error_cnt = 0;
                getOddsInfo(base_url, odds_urls[load_cnt], main_urls[load_cnt]);
            }

            function getOddsInfo(base_url, odds_url, main_url) {
                jQuery.ajax({
                    url: "./api.php",
                    data: {
                        "base_url": base_url,
                        "odds_url": odds_url,
                        "main_url": main_url
                    },
                    method: "POST",
                    success: function(response) {
                        $(".loading").append(response);
                        load_cnt = (load_cnt + 1) % odds_urls.length;
                        flag = false;
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("ajax error!");
                        load_cnt = (load_cnt + 1) % odds_urls.length;
                        flag = false;
                    }
                });
            }