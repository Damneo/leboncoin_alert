<?php
	header('Content-Type: text/html; charset=utf-8');
	include("inc/connect.php");


	//Iterate threw all entries of the table annonces
	$req = "select * from annonces";
	$rep = mysql_query($req);

	while ($data = mysql_fetch_array($rep)) {

		//get last id annonce saw
		$lastId 	= $data['id_annonce'];
		$firstCall 	= ($lastId == "" || $lastId == "0") ? true : false;


		$dom = new DOMDocument();
		
		libxml_use_internal_errors(true);
		$dom->loadHTML(file_get_contents($data['url']));
		libxml_use_internal_errors(false);

		$xpath = new DOMXPath($dom);

		//We get the last annonce id form the web page
		$links = $xpath->query('//div[@class="list-lbc"]/a[1]');

		foreach ($links as $link) {

			$url 	= $link->getAttribute('href');
			preg_match('/\d+/', $url, $matches);
			$firstId 	= $matches[0];
		}

		//The page has already been called
		if (! $firstCall) {

			//There is at least one new in the page since last time
			if ($firstId != $lastId) {

				$news 	= array();
				$cpt	= 0;

				$links = $xpath->query('//div[@class="list-lbc"]/a');
				foreach ($links as $link) {

					//We get the id
					$url 	= $link->getAttribute('href');
					preg_match('/\d+/', $url, $matches);
					$id 	= $matches[0];

					if ($id != $lastId) {

						$title = $xpath->query('.//div[@class="lbc"]/div[@class="detail"]/div[@class="title"]', $link);
						$array_annonce = array("url" => $url, "title" => $link->nodeValue);
						array_push($news, $array_annonce);

						$cpt++;
					} else {
						//formatting mail body
						$news_body_mail = "";

						foreach ($news as $new) {
							$news_body_mail .= "-----------------------------------------------------------<br>";
							$news_body_mail .= $new['title']."<br>";
							$news_body_mail .= "<a href='".$new['url']."'>Lien</a><br>";
							$news_body_mail .= "-----------------------------------------------------------<br>";
						}
						break;
					}
				}

				//Time to send e-mail :)
				$to      = 'dam_is_neo@hotmail.com';
				$subject = 'Nouvelle(s) annonce(s) pour : '.$data['name'];
				$message = 'Bonjour, <br><br>
							Il y a <b>'.$cpt.'</b> nouvelles annonces sur leBonCoin pour l\'alerte : \'<b><a href="'.$data['url'].'">Appartements location 01210</a></b>\'
							<br><br>';
				$message .= $news_body_mail;
				
				// Pour envoyer un mail HTML, l'en-tête Content-type doit être défini
				$headers  = 'MIME-Version: 1.0' . "\r\n";
				$headers .= 'Content-type: text/html; charset=UTF-8' . "\r\n";

				// En-têtes additionnels
				$headers .= 'From: AlerteLeBonCoin <alerteleboncoin@damienrollot.com>' . "\r\n";

				// Envoi
				mail($to, $subject, $message, $headers);

			}
		}

		mysql_query("update annonces set id_annonce = ".$firstId." where id=".$data['id']);
	}
