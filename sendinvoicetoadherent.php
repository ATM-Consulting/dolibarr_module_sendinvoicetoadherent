<?php

require('config.php');
require('./class/sendinvoicetoadherent.class.php');
require('./lib/sendinvoicetoadherent.lib.php');

if(!$user->rights->sendinvoicetoadherent->read) accessforbidden();

$langs->load("sendinvoicetoadherent@sendinvoicetoadherent");

_action();

function _action()
{
	global $user, $db, $conf, $langs;
	$PDOdb=new TPDOdb;
	//$PDOdb->debug=true;
	
	$action=__get('action','list');

	switch($action) {
		case 'list':
			_list($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'listAvoir':
			_listAvoir($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'create':
			_create($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'createConfirm':
			_create_and_send($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'createAvoir':
			_createAvoir($PDOdb, $db, $user, $conf, $langs);

			break;
		default:
			_list($PDOdb, $db, $user, $conf, $langs);

			break;
	}
}

function _list(&$PDOdb, &$db, &$user, &$conf, &$langs, $footer=1)
{
	llxHeader('',$langs->trans('sendinvoicetoadherentTitle'),'','');
	
	$TError = $_SESSION['SENDTOINVOICETOADHERENT_TERROR'];
	$TErrorFac = $_SESSION['SENDTOINVOICETOADHERENT_TERRORFAC'];
	$TErrorMail = $_SESSION['SENDTOINVOICETOADHERENT_TERRORMAIL'];
	
	$sql = _getSql();
	
  	$count = 0;
	if ($PDOdb->Execute($sql))
	{
		$count = $PDOdb->Get_Recordcount();
	}

	print_fiche_titre($langs->trans("sendinvoicetoadherentTitleList"));

	echo '<div class="tabBar">';
	echo '<table class="border" width="100%">';

	echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentCount").'</td><td width="80%">'.$count.'</td></tr>';

	if ($count > 0)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewLinkID").'</td><td width="80%">';
		while ($row = $PDOdb->Get_line())
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/adherents/card.php?rowid='.$row->rowid, 1).'">'.img_picto('', 'object_user').$row->rowid.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}
	
	// Affiche les erreurs à propos de la création des tiers non réussite 
	if ($TError)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewListErrorCreateTiers").'</td><td width="80%">';
		foreach ($TError as $fk_adherent)
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/adherents/card.php?rowid='.$fk_adherent, 1).'">'.img_picto('', 'object_user').$fk_adherent.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}
	
	// Affiche les erreurs à propos de la création des factures 
	if ($TErrorFac)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewListErrorCreateFacture").'</td><td width="80%">';
		foreach ($TErrorFac as $fk_societe)
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/societe/soc.php?socid='.$fk_societe, 1).'">'.img_picto('', 'object_user').$fk_societe.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}
	
	// Affiche les erreurs à propos des envois de mail
	if ($TErrorMail)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewListErrorMailSend").'</td><td width="80%">';
		foreach ($TErrorMail as $fk_societe)
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/societe/soc.php?socid='.$fk_societe, 1).'">'.img_picto('', 'object_user').$fk_societe.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}

	echo '</table></div>';

	if ($user->rights->sendinvoicetoadherent->create)
	{
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="'.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=create', 1).'">'.$langs->trans('sendinvoicetoadherentActionCreate').'</a>';
		echo '</div>';
	}

	if ($footer)
	{
		$PDOdb->close();
		llxFooter('');
	}

}

function _listAvoir(&$PDOdb, &$db, &$user, &$conf, &$langs, $footer=1)
{
	llxHeader('',$langs->trans('sendinvoicetoadherentTitleAvoir'),'','');
	
	print_fiche_titre($langs->trans("sendinvoicetoadherentTitleListAvoir"));

	$TFetchError = $_SESSION['SENDTOINVOICETOADHERENT_TFETCHERROR'];
	$TCreateError = $_SESSION['SENDTOINVOICETOADHERENT_TCREATEERROR'];
	
	unset($_SESSION['SENDTOINVOICETOADHERENT_TFETCHERROR']);
	unset($_SESSION['SENDTOINVOICETOADHERENT_TCREATEERROR']);
	
	$sql = _getSql2();
	
  	$count = 0;
	if ($PDOdb->Execute($sql))
	{
		$count = $PDOdb->Get_Recordcount();
	}

	echo '<div class="tabBar">';
	echo '<table class="border" width="100%">';

	echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentCountUnpaidInvoice").'</td><td width="80%">'.$count.'</td></tr>';

	if ($count > 0)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewLinkID").'</td><td width="80%">';
		$old_id = 0;
		$TAdherent = array();
		//var_dump($TAdherent);
		while ($row = $PDOdb->Get_line())
		{
			if (isset($TAdherent[$row->rowid])) $TAdherent[$row->rowid][] = $row->facnumber;
			else $TAdherent[$row->rowid] = array($row->facnumber);
		}
		
		foreach ($TAdherent as $fk_adherent => $TFacnumber)
		{
			echo '<span><a target="_blank" href="'.dol_buildpath('/adherents/card.php?rowid='.$fk_adherent, 1).'">'.img_picto('', 'object_user').$fk_adherent.'&nbsp;</a>';
			echo '[';
			
			$nbFac = count($TFacnumber);
			$i = 0;
			foreach ($TFacnumber as $facnumber)
			{
				$i++;
				echo '<a href="'.dol_buildpath('/compta/facture.php?ref='.$facnumber, 2).'">';
				if ($i > 1) echo '<span style="color:red;">';
				echo $facnumber;
				if ($i > 1) echo '</span>';
				echo '</a>';
				
				if ($nbFac > 1 && $i < $nbFac) echo ' - ';
			}
			
			echo ']</span>&nbsp;&nbsp;';
		}

		echo '</td><tr>';
	}


	// Affiche les erreurs à propos des fetch en échec
	if ($TFetchError)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewListErrorFetchFac").'</td><td width="80%">';
		foreach ($TFetchError as $facnumber)
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/compta/facture.php?ref='.$facnumber, 1).'">'.img_picto('', 'object_bill').$facnumber.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}

	// Affiche les erreurs à propos des créations d'avoirs
	if ($TCreateError)
	{
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewListErrorCreateAvoir").'</td><td width="80%">';
		foreach ($TCreateError as $facnumber)
		{
			 echo '<a target="_blank" style="float:left;" href="'.dol_buildpath('/compta/facture.php?ref='.$facnumber, 1).'">'.img_picto('', 'object_bill').$facnumber.'&nbsp;</a>';
		}

		echo '</td><tr>';
	}

	echo '</table></div>';

	if ($user->rights->sendinvoicetoadherent->create)
	{
		echo '<div class="tabsAction">';
		echo '<a class="butAction" href="'.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=createAvoir', 1).'">'.$langs->trans('sendinvoicetoadherentActionCreateAvoir').'</a>';
		echo '</div>';
	}

	$PDOdb->close();
	llxFooter('');
}

function _create(&$PDOdb, &$db, &$user, &$conf, &$langs, $df=false, $ds=false, $de=false, $amount=0, $label='')
{
	unset($_SESSION['SENDTOINVOICETOADHERENT_ERRORS']);
	
	_list($PDOdb, $db, $user, $conf, $langs, 0);

	if(!$user->rights->sendinvoicetoadherent->create) accessforbidden();
	else
	{
		$form=new TFormCore;
		
		print_fiche_titre($langs->trans("sendinvoicetoadherentTitleCreate"));
		echo '<script type="text/javascript">
			$(function() {
				displayDateCotisation();
			});
			
			function displayDateCotisation(obj) {
				if ($(obj).attr("checked") == "checked") {
					$(".cotisation_create").show()
				} else {
					$(".cotisation_create").hide()
				}
			}
		</script>';
		
		echo '<form style="padding-top:15px;" name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		echo '<input type="hidden" name="action" value="createConfirm">';

		echo '<table class="border" width="100%">';

		$date_fac = $df === false ? date('d/m/Y') : $df;
		$date_start = $ds === false ? date('d/m/Y') : $ds;
		$date_end = $de === false ? date('d/m/Y', strtotime('+1 year -1 day')) : $de;
		$label = empty($label) ? $langs->trans("Subscription").' '.($ds ? substr($date_start, -4, 4) : dol_print_date(time(),'%Y')) : $label;
		
		echo '<tr><td width="20%" class="fieldrequired">'.$langs->trans("sendinvoicetoadherentDateFacture").'</td><td width="80%">'.$form->calendrier('', 'date_fac', $date_fac).'</td></tr>';
		
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentCreateCotisation").'</td><td width="80%"><input type="checkbox" name="create_cotisation" value="1" onclick="javascript:displayDateCotisation(this);" /></td></tr>';
		echo '<tr class="cotisation_create" style="display:none;"><td width="20%" class="fieldrequired">'.$langs->trans("sendinvoicetoadherentDateStartAdhesion").'</td><td width="80%">'.$form->calendrier('', 'date_start', $date_start).'</td></tr>';
		echo '<tr class="cotisation_create" style="display:none;"><td width="20%">'.$langs->trans("sendinvoicetoadherentDateEndAdhesion").'</td><td width="80%">'.$form->calendrier('', 'date_end', $date_end).'</td></tr>';
		echo '<tr class="cotisation_create" style="display:none;"><td width="20%" class="fieldrequired">'.$langs->trans("sendinvoicetoadherentAmountAdhesion").'</td><td width="80%">'.$form->texte('', 'amount_cotisation', $amount, 6, 15).' '.$langs->trans("Currency".$conf->currency).'</td></tr>';
		echo '<tr class="cotisation_create" style="display:none;"><td width="20%">'.$langs->trans("sendinvoicetoadherentLabelAdhesion").'</td><td width="80%">'.$form->texte('', 'label', $label, 32, 255).'</td></tr>';

		echo '</table>';

		if ($user->rights->sendinvoicetoadherent->create)
		{
			echo '<br /><center>';
			echo '<input type="submit" class="button" value="'.$langs->trans('sendinvoicetoadherentActionCreateConfirm').'" />&nbsp;&nbsp;&nbsp;&nbsp;';
			echo '<a href="'.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=list',2).'" class="button" style="text-decoration:none;font-weight:normal;cusor:pointer;height:15px;padding-top:5px;">'.$langs->trans('Cancel').'</a>';
			echo '</center>';
		}

		echo '</form>';
	}
	
	$PDOdb->close();
	llxFooter('');
}

function _create_and_send($PDOdb, $db, $user, $conf, $langs)
{
	unset($_SESSION['SENDTOINVOICETOADHERENT_TERROR']);
	unset($_SESSION['SENDTOINVOICETOADHERENT_TERRORFAC']);
	unset($_SESSION['SENDTOINVOICETOADHERENT_TERRORMAIL']);
	
	$error = 0;
	$nb_mail_sent = 0;
	
	if(!$user->rights->sendinvoicetoadherent->create)
	{
		$error++;
		setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateNotPermitted'), false, 'errors');
		header('Location: '.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=list', 2));
		exit;
	}
	
	dol_include_once('/compta/facture/class/facture-rec.class.php');
	dol_include_once('/compta/facture/class/adherent.class.php');
	dol_include_once('/adherents/class/adherent.class.php');
	dol_include_once('/societe/class/societe.class.php');
	dol_include_once('/core/class/CMailFile.class.php');
	dol_include_once('/core/lib/files.lib.php');

	$date_fac = GETPOST('date_fac', 'alpha');
	$create_cotisation = GETPOST('create_cotisation', 'int');
	$date_start = GETPOST('date_start', 'alpha');
	$date_end = GETPOST('date_end', 'alpha');
	$amount = price2num(GETPOST('amount_cotisation', 'alpha'), 2);
	$label = GETPOST('label', 'alpha');
	
	$TDate_fac = explode('/' , $date_fac);
	if (!checkdate($TDate_fac[1], $TDate_fac[0], $TDate_fac[2]))
	{
		$error++;
		setEventMessages($langs->trans('sendinvoicetoadherentErrorDateFac'), false, 'errors');
	}
	
	if ($create_cotisation)
	{
		$TDate_start = explode('/', $date_start);
		$TDate_end = explode('/', $date_end);
		if (!checkdate($TDate_start[1], $TDate_start[0], $TDate_start[2]) || !checkdate($TDate_end[1], $TDate_end[0], $TDate_end[2]))
		{
			$error++;
			setEventMessages($langs->trans('sendinvoicetoadherentErrorDate'), false, 'errors');
		}	
	}
	
	$fk_facture_rec = (int) $conf->global->SENDINVOICETOADHERENT_FK_FACTURE;
	if (!$fk_facture_rec)
	{
		$error++;
		setEventMessages($langs->trans('sendinvoicetoadherent_fk_facture'), false, 'errors');
	}

	if (!$error)
	{
		$sql = _getSql();
		
		if ($PDOdb->Execute($sql))
		{
			$TError = $TErrorFac = $TErrorMail = array();
			while ($row = $PDOdb->Get_line())
			{
				$ad = new Adherent($db);
				$ad->fetch($row->rowid);

				if (!$ad->fk_soc && $ad->societe) $societe = _createTiers($db, $user, $ad);
				else
				{
					$ad->fetch_thirdparty();
					$societe = $ad->thirdparty;
				}
	
				if ($societe && $societe->id > 0)
				{
					if ($create_cotisation)
					{
				        if ($ad->cotisation(dol_mktime(0, 0, 0, $TDate_start[1], $TDate_start[0], $TDate_start[2]), $amount, 0, '', $label, '', '', '', dol_mktime(0, 0, 0, $TDate_end[1], $TDate_end[0], $TDate_end[2])) <= 0)
				        {
				            $error++;
					        setEventMessages($object->error,$object->errors, 'errors');
				        }
					}
					
					$factureRec = new FactureRec($db);
					$factureRec->fetch($fk_facture_rec);

					$facture = new Facture($db);
					$facture->brouillon = 1;
					$facture->socid = $societe->id;
					$facture->type = Facture::TYPE_STANDARD;
					$facture->fk_project        = $factureRec->fk_project;
					$facture->cond_reglement_id = $factureRec->cond_reglement_id;
					$facture->mode_reglement_id = $factureRec->mode_reglement_id;
					$facture->remise_absolue    = $factureRec->remise_absolue;
					$facture->remise_percent    = $factureRec->remise_percent;
					$facture->date = dol_mktime(12, 0, 0, $TDate_fac[1], $TDate_fac[0], $TDate_fac[2]);
					$facture->note_private = $factureRec->note_private;
					$facture->note_public = $factureRec->note_public;
					$facture->lines = $factureRec->lines;
					
					if ($facture->create($user) > 0) 
					{
						 $facture->validate($user);
						 if (!_sendByMail($db, $conf, $user, $langs, $facture, $societe, $label))
						 {
						 	$TErrorMail[] = $societe->id;
						 }
					}
					else $TErrorFac[] = $societe->id;

				}
				else
				{
					$TError[] = $ad->id;
				}
	
			}
	
			if (count($TError) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateTiers', count($TError)), null, 'errors');
			if (count($TErrorFac) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateFacture', count($TErrorFac)), null, 'errors');
			if (count($TErrorMail) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorMailSend', count($TErrorMail)), null, 'errors');
			else setEventMessages($langs->trans('sendinvoicetoadherentConfirmCreate', $PDOdb->Get_Recordcount()), null);
	
			$_SESSION['SENDTOINVOICETOADHERENT_ERRORS'] = array('TError' => $TError, 'TErrorFac' => $TErrorFac, '');
			
			$_SESSION['SENDTOINVOICETOADHERENT_TERROR'] = $TError;
			$_SESSION['SENDTOINVOICETOADHERENT_TERRORFAC'] = $TErrorFac;
			$_SESSION['SENDTOINVOICETOADHERENT_TERRORMAIL'] = $TErrorMail;
	
			header('Location: '.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=list', 2));
			exit;
		}
	}
	else
	{
		_create($PDOdb, $db, $user, $conf, $langs, $date_fac, $date_start, $date_end, $amount, $label);
	}

}

function _createTiers(&$db, &$user, &$ad)
{
	$societe = new Societe($db);

	$name = trim($ad->firstname.' '.$ad->lastname);
	if (!empty($name)) $name .= ' / '.$ad->societe;
	else $name = $ad->societe;

	$societe->name = $name;
	$societe->client = 1;

	if (!empty($ad->email) && isValidEMail($ad->email)) $societe->email = $ad->email;

	$societe->address = $ad->address;
	$societe->zip = $ad->zip;
	$societe->town = $ad->town;
	$societe->state_id = $ad->state_id;
	$societe->country_id = $ad->country_id;
	$societe->phone = $ad->phone;

	if ($societe->create($user) > 0) { $ad->fk_soc = $societe->id; return $societe; }
	else return false;
}

function _sendByMail(&$db, &$conf, &$user, &$langs, &$facture, &$societe, $label)
{	
	$filename_list = array();
	$mimetype_list = array();
	$mimefilename_list = array();
		
	$ref = dol_sanitizeFileName($facture->ref);
	$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/').'([^\-])+');
	$file = $fileparams ['fullname'];
	
	// Build document if it not exists
	if (! $file || ! is_readable($file)) 
	{
		$result = $facture->generateDocument($facture->modelpdf, $langs, 0, 0, 0);
		if ($result <= 0) 
		{
			$error = 1;
			return $error;
		}
	}
	
	$label = !empty($conf->global->SENDINVOICETOADHERENT_SUBJECT) ? $conf->global->SENDINVOICETOADHERENT_SUBJECT : $label;
	
	$substitutionarray=array(
		'__NAME__' => $societe->name
		,'__REF__' => $facture->ref
	);

	$message=$conf->global->SENDINVOICETOADHERENT_MESSAGE;
	$message=make_substitutions($message, $substitutionarray);

	$fileparams = dol_most_recent_file($conf->facture->dir_output . '/' . $ref, preg_quote($ref, '/').'([^\-])+');
	$file = $fileparams ['fullname'];	
	$filename = basename($file);
	$mimefile=dol_mimetype($file);
	
	$filename_list[] = $file;
	$mimetype_list[] = $mimefile;
	$mimefilename_list[] = $filename;
	
	$CMail = new CMailFile(	
		$label
		,$societe->email
		,$conf->global->MAIN_MAIL_EMAIL_FROM
		,$message
		,$filename_list
		,$mimetype_list
		,$mimefilename_list
		,'' //,$addr_cc=""
		,'' //,$addr_bcc=""
		,'' //,$deliveryreceipt=0
		,'' //,$msgishtml=0
		,$errors_to=$conf->global->MAIN_MAIL_ERRORS_TO
		//,$css=''
	);
	
	// Send mail
	return $CMail->sendfile();
}

function _createAvoir(&$PDOdb, &$db, &$user, &$conf, &$langs)
{
	dol_include_once('/compta/facture/class/facture.class.php');
	dol_include_once('/core/class/discount.class.php');
	
	$sql = _getSql2();
	$PDOdb->Execute($sql);
	$TFacnumberFetchError = array();
	$TFacnumberCreateError = array();
	$TDiscountCreateError = array();
	$nbValidate = 0;
	
	while ($row = $PDOdb->Get_line()) 
	{
		$fk_soc = $row->fk_soc;
		$facnumber = $row->facnumber;
	
		$factureImpayee = new Facture($db);
		if ($factureImpayee->fetch(null, $facnumber) <= 0)
		{
			$TFacnumberFetchError[] = $facnumber;
			continue;
		}
		
		$dateinvoice = dol_mktime(12, 0, 0, date('m'), date('d'), date('Y'));
		
		$facture = new Facture($db);
		
		$facture->socid = $fk_soc;
		$facture->fk_facture_source = $factureImpayee->id;
		$facture->type = Facture::TYPE_CREDIT_NOTE;
		$facture->date = $dateinvoice;
		
		if ($facture->create($user) <= 0) 
		{
			$TFacnumberCreateError[] = $facnumber;
			continue;
		}
		
		foreach($factureImpayee->lines as $line)
        {
            $line->fk_facture = $facture->id;

            $line->subprice =-$line->subprice; // invert price for object
            $line->pa_ht = -$line->pa_ht;
            $line->total_ht=-$line->total_ht;
            $line->total_tva=-$line->total_tva;
            $line->total_ttc=-$line->total_ttc;
            $line->total_localtax1=-$line->total_localtax1;
            $line->total_localtax2=-$line->total_localtax2;

            $line->insert();

            $facture->lines[] = $line; // insert new line in current object
        }

        $facture->update_price(1);
		$facture->validate($user);
		
		$discountcheck=new DiscountAbsolute($db);
		$result=$discountcheck->fetch(0,$facture->id);


		if (!empty($discountcheck->id))
		{
			//can't convert
			$facture->delete();
			continue;
		}

		$i = 0;
		$amount_ht = $amount_tva = $amount_ttc = array();
		foreach ($facture->lines as $line) {
			if($line->total_ht!=0) { // no need to create discount if amount is null
				$amount_ht [$line->tva_tx] += $line->total_ht;
				$amount_tva [$line->tva_tx] += $line->total_tva;
				$amount_ttc [$line->tva_tx] += $line->total_ttc;
				$i ++;
			}
		}

		// Insert one discount by VAT rate category
		$discount = new DiscountAbsolute($db);
		$discount->description = '(CREDIT_NOTE)';
		
		$discount->tva_tx = abs($facture->total_ttc);
		$discount->fk_soc = $facture->socid;
		$discount->fk_facture_source = $facture->id;

		foreach ($amount_ht as $tva_tx => $xxx) 
		{
			$discount->amount_ht = abs($amount_ht [$tva_tx]);
			$discount->amount_tva = abs($amount_tva [$tva_tx]);
			$discount->amount_ttc = abs($amount_ttc [$tva_tx]);
			$discount->tva_tx = abs($tva_tx);

			$result = $discount->create($user);
			if ($result < 0)
			{
				$TDiscountCreateError[] = $facnumber;
				$error++;
				break;
			}

			$result = $facture->set_paid($user);
			$result = $discount->link_to_invoice(0, $factureImpayee->id);
			
			$r = $factureImpayee->set_paid($user);
		}


/******/

		$nbValidate++;
	}

	if ($nbValidate) setEventMessages($langs->trans('sendinvoicetoadherentAvoirValidate', $nbValidate), null);
	if (count($TFacnumberFetchError) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorFetchFacture', count($TFacnumberFetchError)), null, 'errors');
	if (count($TFacnumberCreateError) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateAvoir', count($TFacnumberCreateError)), null, 'errors');
	
	$_SESSION['SENDTOINVOICETOADHERENT_TFETCHERROR'] = $TFacnumberFetchError;
	$_SESSION['SENDTOINVOICETOADHERENT_TCREATEERROR'] = $TFacnumberCreateError;
	
	header('Location: '.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=listAvoir', 2));
	exit;
}

function _getSql()
{
	global $conf;
	
	$fk_product_cotisation = (int) $conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS;
	
	return "
		SELECT a.rowid 
		FROM ".MAIN_DB_PREFIX."adherent a 
		WHERE a.entity = 1 
		AND a.statut <> -1 # Pas d'adhérent en brouillon
		AND a.rowid NOT IN (SELECT cc.fk_adherent FROM llx_cotisation cc WHERE CURRENT_DATE BETWEEN cc.dateadh AND cc.datef) # n'est pas dans la liste des adhérents ayant une cotisation pour l'année en cours
		AND a.rowid NOT IN ( # n'est pas dans la liste des adhérents ayant une ou +sieurs facture (je prend la plus récente) dont la date est de moins d'un an (cotisation à l'année)
		    SELECT aa.rowid     
		    FROM ".MAIN_DB_PREFIX."adherent aa 
		    INNER JOIN ".MAIN_DB_PREFIX."facture f ON (f.fk_soc = aa.fk_soc AND f.entity = 1 AND f.fk_statut >= 1)		    
		    WHERE f.rowid IN (SELECT fd.fk_facture FROM ".MAIN_DB_PREFIX."facturedet fd WHERE fk_product = ".$fk_product_cotisation.")
		    AND f.datef = ( # filtre pour récupérer la facture la plus récente
				SELECT MAX(ff.datef) 
                FROM ".MAIN_DB_PREFIX."facture ff 
                INNER JOIN ".MAIN_DB_PREFIX."facturedet ffd ON (ffd.fk_facture = ff.rowid AND ffd.fk_product = ".$fk_product_cotisation.")
                WHERE ff.fk_soc = f.fk_soc
		    )
		    AND YEAR(f.datef) > YEAR(CURDATE()) - 1
		)
	";
}

function _getSql2()
{
	global $conf;
	
	$fk_product_cotisation = (int) $conf->global->ADHERENT_PRODUCT_ID_FOR_SUBSCRIPTIONS;
	
	//Attention la requete peut renvoyer plusieurs fois le même id adhérent (s'il a +sieurs datef identique au max)
	return "
		SELECT a.rowid, a.fk_soc, f.facnumber #Je veux la liste des adhérents avec leur facture impayée 

		FROM llx_adherent a 
		INNER JOIN llx_societe s ON (s.rowid = a.fk_soc) 
		INNER JOIN llx_facture f ON (f.fk_soc = s.rowid) 
		WHERE a.entity = 1
		AND f.datef = ( # filtre pour récupérer la facture la plus récente 
		    SELECT MAX(ff.datef) 
		    FROM llx_facture ff 
		    INNER JOIN llx_facturedet ffd ON (ffd.fk_facture = ff.rowid AND ffd.fk_product = 1) 
		    WHERE ff.fk_soc = f.fk_soc 
		    AND ff.type = 0
		    AND ff.fk_statut IN (0, 1)
		    AND ff.datef > (CURDATE() - INTERVAL 1 YEAR)
		    AND ff.rowid NOT IN (SELECT fff.fk_facture_source FROM llx_facture fff WHERE type = 2)
		)
	";
}
