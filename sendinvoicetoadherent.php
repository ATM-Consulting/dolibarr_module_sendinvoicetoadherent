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
			_liste($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'create':
			_create($PDOdb, $db, $user, $conf, $langs);

			break;
		case 'createConfirm':
			_create_and_send($PDOdb, $db, $user, $conf, $langs);

			break;
		default:
			_liste($PDOdb, $db, $user, $conf, $langs);

			break;
	}
}

function _liste(&$PDOdb, &$db, &$user, &$conf, &$langs, $footer=1)
{
	llxHeader('',$langs->trans('sendinvoicetoadherentTitle'),'','');
	
	$TError = $_SESSION['SENDTOINVOICETOADHERENT_ERRORS']['TError'];
	$TErrorFac = $_SESSION['SENDTOINVOICETOADHERENT_ERRORS']['TErrorFac'];
	
	// Un BETWEEN est inclusif des 2 côtés
	$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."adherent a WHERE entity = ".$conf->entity." AND rowid NOT IN (SELECT fk_adherent FROM ".MAIN_DB_PREFIX."cotisation WHERE CURRENT_DATE BETWEEN dateadh AND datef)";
	
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

function _create(&$PDOdb, &$db, &$user, &$conf, &$langs, $df=false, $ds=false, $de=false, $amount=0, $label='')
{
	unset($_SESSION['SENDTOINVOICETOADHERENT_ERRORS']);
	
	_liste($PDOdb, $db, $user, $conf, $langs, 0);

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
	unset($_SESSION['SENDTOINVOICETOADHERENT_ERRORS']);
	$error = 0;
	
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
		$sql="SELECT rowid FROM ".MAIN_DB_PREFIX."adherent a WHERE entity = ".$conf->entity." AND rowid NOT IN (SELECT fk_adherent FROM ".MAIN_DB_PREFIX."cotisation WHERE CURRENT_DATE BETWEEN dateadh AND datef)";
		
		if ($PDOdb->Execute($sql))
		{
			$TError = $TErrorFac = array();
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
						 _sendByMail($db, $conf, $user, $langs, $facture, $societe, $label);
					}
					else $TErrorFac[] = $societe;
	
				}
				else
				{
					$TError[] = $ad;
				}
	
			}
	
			if (count($TError) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateTiers', count($TError)), null, 'errors');
			if (count($TErrorFac) > 0) setEventMessages($langs->trans('sendinvoicetoadherentErrorCreateFacture', count($TErrorFac)), null, 'errors');
			else setEventMessages($langs->trans('sendinvoicetoadherentConfirmCreate', $PDOdb->Get_Recordcount()), null);
	
			$_SESSION['SENDTOINVOICETOADHERENT_ERRORS'] = array('TError' => $TError, 'TErrorFac' => $TErrorFac);
	
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
	$CMail->sendfile();
	
}
