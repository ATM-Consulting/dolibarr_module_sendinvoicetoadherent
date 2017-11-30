<?php

require('config.php');
require('./class/sendinvoicetoadherent.class.php');
require('./lib/sendinvoicetoadherent.lib.php');
dol_include_once('/societe/class/societe.class.php');
dol_include_once('/compta/facture/class/facture.class.php');

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
		$societe = new Societe($db);
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewLinkID").'</td><td width="80%">';
		while ($row = $PDOdb->Get_line())
		{
			$societe->fetch($row->rowid);
			echo $societe->getNomUrl(1).'<br>';
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

	if ($user->rights->sendinvoicetoadherent->create && $count > 0)
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
		$facture = new Facture($db);
		echo '<tr><td width="20%">'.$langs->trans("sendinvoicetoadherentViewLinkID").'</td><td width="80%">';
		while ($row = $PDOdb->Get_line())
		{
			$facture->fetch($row->$rowid);
			$facture->fetch_thirdparty();
			echo $facture->getNomUrl(1) . ' - '. $facture->thirdparty->getNomUrl(1).'<br>';
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

	if ($user->rights->sendinvoicetoadherent->create && $count > 0)
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
		
		echo '<form style="padding-top:15px;" name="formsoc" action="'.$_SERVER["PHP_SELF"].'" method="post" enctype="multipart/form-data">';
		echo '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		echo '<input type="hidden" name="action" value="createConfirm">';

		echo '<table class="border" width="100%">';

		$date_fac = $df === false ? date('d/m/Y') : $df;
		$date_start = $ds === false ? date('d/m/Y') : $ds;
		$date_end = $de === false ? date('d/m/Y', strtotime('+1 year -1 day')) : $de;
		$label = empty($label) ? $langs->trans("Subscription").' '.($ds ? substr($date_start, -4, 4) : dol_print_date(time(),'%Y')) : $label;
		
		echo '<tr><td width="20%" class="fieldrequired">'.$langs->trans("sendinvoicetoadherentDateFacture").'</td><td width="80%">'.$form->calendrier('', 'date_fac', $date_fac).'</td></tr>';

		echo '</table>';
		
		echo '<br /><center>';
		echo '<input type="submit" class="button" value="'.$langs->trans('sendinvoicetoadherentActionCreateConfirm').'" />&nbsp;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.dol_buildpath('/sendinvoicetoadherent/sendinvoicetoadherent.php?action=list',2).'" class="button" style="text-decoration:none;font-weight:normal;cusor:pointer;height:15px;padding-top:5px;">'.$langs->trans('Cancel').'</a>';
		echo '</center>';

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
	$date_start = strtotime('first day of april');
	$date_end = strtotime('+1year -1day', $date_start);
	
	$TDate_fac = explode('/' , $date_fac);
	if (!checkdate($TDate_fac[1], $TDate_fac[0], $TDate_fac[2]))
	{
		$error++;
		setEventMessages($langs->trans('sendinvoicetoadherentErrorDateFac'), false, 'errors');
	}
	
	$product = new Product($db);
	$product->fetch(0,'ADI');
	$TProductTypo['INT'] = $product;
	$product = new Product($db);
	$product->fetch(0,'ADE');
	$TProductTypo['EXT'] = $product;
	
	if (!$error)
	{
		$sql = _getSql();
		
		if ($PDOdb->Execute($sql))
		{
			$TError = $TErrorFac = $TErrorMail = array();
			while ($row = $PDOdb->Get_line())
			{
				$societe = new Societe($db);
				$res = $societe->fetch($row->rowid);
				
				if ($res == 1)
				{
					$typo = $societe->array_options['options_typologie'];
					$prod = $TProductTypo[$typo];
					
					$facture = new Facture($db);
					$facture->brouillon = 1;
					$facture->socid = $societe->id;
					$facture->type = Facture::TYPE_STANDARD;
					$facture->cond_reglement_id = 1;
					$facture->mode_reglement_id = 0;
					$facture->date = dol_mktime(12, 0, 0, $TDate_fac[1], $TDate_fac[0], $TDate_fac[2]);
					
					if ($facture->create($user) > 0) 
					{
						$facture->addline('', $prod->price, 1, $prod->tva_tx,0,0,$prod->id,0,$date_start,$date_end);
						$facture->validate($user);
						$facture->generateDocument($facture->modelpdf, $langs);
					}
					else $TErrorFac[] = $societe->id;

				}
				else
				{
					$TError[] = $societe->id;
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
		$factureImpayee = new Facture($db);
		if ($factureImpayee->fetch($row->rowid) <= 0)
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
	// On récupère tous les tiers pour lesquels ils faut facturer l'adhésion pour l'année en cours
	$sql = 'SELECT s.rowid ';
	$sql.= 'FROM '.MAIN_DB_PREFIX.'societe s ';
	$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'societe_extrafields sext ON (sext.fk_object = s.rowid) ';
	$sql.= 'WHERE sext.typologie IN (\'INT\',\'EXT\')';
	$sql.= 'AND s.rowid NOT IN ( '; // N' pas encore eu de facture d'adhésion (service ADI ou ADE) sur l'année en cours
	$sql.= '	SELECT f.fk_soc ';
	$sql.= '	FROM '.MAIN_DB_PREFIX.'facture f ';
	$sql.= '	LEFT JOIN '.MAIN_DB_PREFIX.'facturedet fdet ON (fdet.fk_facture = f.rowid) ';
	$sql.= '	LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (fdet.fk_product = p.rowid) ';
	$sql.= '	WHERE (p.ref IN (\'ADI\',\'ADE\') ';
	$sql.= '	OR fdet.description LIKE \'%Adhésion%\') ';
	$sql.= '	AND YEAR(f.datef) = YEAR(CURDATE()) ';
	$sql.= ')';
	
	return $sql;
}

function _getSql2()
{
	// On récupère tous les tiers / factures pour lesquels ils faut faire un avoir sur l'adhésion pour l'année en cours (facture impayée)
	$sql = 'SELECT f.rowid ';
	$sql.= 'FROM '.MAIN_DB_PREFIX.'facture f ';
	$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'facturedet fdet ON (fdet.fk_facture = f.rowid) ';
	$sql.= 'LEFT JOIN '.MAIN_DB_PREFIX.'product p ON (fdet.fk_product = p.rowid) ';
	$sql.= 'WHERE (p.ref IN (\'ADI\',\'ADE\') ';
	$sql.= 'OR fdet.description LIKE \'%Adhésion%\') ';
	$sql.= 'AND YEAR(f.datef) = YEAR(CURDATE()) ';
	$sql.= 'AND f.paye = 0 ';
	
	return $sql;
}
