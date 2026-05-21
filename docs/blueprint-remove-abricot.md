# Blueprint - Rendre FullCalendar independant d'ABRICOT

## Objectif

Supprimer la dependance obligatoire au framework ABRICOT afin que le module `fullcalendar` puisse etre installe, active et utilise dans un Dolibarr standard, sans module tiers `abricot`.

Le module doit rester compatible avec Dolibarr >= 16 et PHP >= 7.0, comme declare actuellement dans `core/modules/modfullcalendar.class.php`.

## Constat technique

Dependances ABRICOT identifiees :

- `config.default.php`
  - charge `main.inc.php` ou `master.inc.php`.
  - definit des constantes `DB_*` heritees d'ABRICOT.
  - appelle obligatoirement `dol_include_once('/abricot/inc.core.php')`, puis interrompt l'execution si ABRICOT est absent.
- `script/create-maj-base.php`
  - contient un squelette commente utilisant `TPDOdb`, classe ABRICOT.
  - le script est appele pendant l'activation du module depuis `modfullcalendar::init()`.
- Aucune classe metier active ne repose sur `TObjetStd`, `TFormCore`, `TListview`, `ATMdb` ou `TPDOdb`.
- Le module ne declare pas de tables SQL propres et ne possede pas de dossier `sql/`.
- Les usages de `fk_soc` concernent les tables natives Dolibarr `actioncomm` et les formulaires natifs, pas une table propre au module.

Dependances ATM non bloquantes mais a traiter pour un module libre/autonome :

- `class/techatm.class.php` effectue des appels reseau vers `https://tech.atm-consulting.fr`.
- `admin/fullcalendar_about.php` depend de `TechATM` pour afficher la page a propos.
- `core/modules/modfullcalendar.class.php` utilise `TechATM::getLastModuleVersionUrl()`.
- Les langues contiennent des textes commerciaux `ATMAbout`.

## Critique du plan initial implicite

Une suppression brute de `dol_include_once('/abricot/inc.core.php')` suffit probablement a lever le blocage immediat, mais ce serait incomplet :

- `script/create-maj-base.php` garderait une reference ABRICOT commente mais trompeuse, appelee a l'activation.
- `config.default.php` conserverait des constantes `DB_*` inutiles, issues du bootstrap ABRICOT historique.
- La page "About" continuerait a faire des appels reseau externes, ce qui contredit l'objectif d'un module autonome.
- Le descripteur garderait une URL de derniere version calculee par une classe ATM, creant une dependance fonctionnelle non necessaire au chargement du module.
- Il faut verifier les flux AJAX, car `script/interface.php` est le coeur du module et passe par `config.php`.

Conclusion critique : le bon decouplage doit traiter le bootstrap, l'activation, la page about et le descripteur. La base de donnees n'a pas besoin de migration.

## Arborescence cible

Fichiers a modifier :

- `config.default.php`
- `config.php`
- `script/create-maj-base.php`
- `core/modules/modfullcalendar.class.php`
- `admin/fullcalendar_about.php`
- `langs/fr_FR/fullcalendar.lang`
- `langs/en_US/fullcalendar.lang`
- `README`
- `ChangeLog.md`

Fichiers a creer :

- `class/FullcalendarAboutPage.class.php`
- `test/phpunit/FullcalendarBootstrapTest.php` ou, si le projet ne dispose pas de PHPUnit local, `script/test_remove_abricot_dependency.php`

Fichiers a conserver sans changement fonctionnel :

- `class/techatm.class.php` peut etre laisse pour compatibilite descendante si aucune page ne l'appelle plus. Il pourra etre supprime dans une version majeure seulement.

## Design final propose

### 1. Bootstrap Dolibarr natif

Modifier `config.default.php` pour ne charger que l'environnement Dolibarr.

Pseudo-code :

```text
detecter $dir :
  si ../main.inc.php existe : $dir = ../
  sinon si ../../../main.inc.php existe : $dir = ../../../
  sinon : $dir = ../../

si INC_FROM_DOLIBARR non defini :
  si INC_FROM_CRON_SCRIPT defini :
    require_once $dir . 'master.inc.php'
  sinon :
    require_once $dir . 'main.inc.php'
sinon :
  ne rien charger, l'environnement Dolibarr est deja present

ne pas definir DB_HOST, DB_NAME, DB_USER, DB_PASS, DB_DRIVER
ne pas charger ABRICOT
ne pas interrompre si ABRICOT est absent
```

Points d'attention :

- `config.php` peut rester un simple proxy vers `config.default.php`.
- Utiliser `require_once` plutot que `include` pour eviter un double chargement accidentel.
- Ne pas utiliser `dol_include_once()` avant que `main.inc.php` soit charge.

### 2. Activation du module

Modifier `core/modules/modfullcalendar.class.php`.

Pseudo-code pour le constructeur :

```text
initialiser metadonnees du module
ne pas charger TechATM
mettre url_last_version a une chaine vide ou a une URL statique de documentation si souhaite
conserver module_parts hooks agenda/agendalist
conserver droits et menus existants
```

Pseudo-code pour `init()` :

```text
define INC_FROM_DOLIBARR si non defini
dol_include_once('/fullcalendar/config.php')

si un dossier /fullcalendar/sql existe :
  result = _load_tables('/fullcalendar/sql/')
  si result < 0 : retourner -1

ne plus inclure script/create-maj-base.php sauf si ce script contient une migration native utile
retourner _init(sql, options)
```

Justification :

- Le script `create-maj-base.php` n'effectue aucune migration active.
- `_load_tables('/fullcalendar/sql/')` est inutile tant que le dossier n'existe pas, mais peut rester conditionnel pour compatibilite future.

### 3. Script de migration legacy

Modifier `script/create-maj-base.php` pour en faire un no-op natif ou le deprecier.

Pseudo-code :

```text
si INC_FROM_DOLIBARR non defini :
  definir INC_FROM_CRON_SCRIPT
  require '../config.php'

retourner 1
```

Supprimer du fichier :

- le commentaire mentionnant `TPDOdb`.
- l'exemple `TXXX`.
- toute reference ABRICOT.

### 4. Page a propos autonome

Creer `class/FullcalendarAboutPage.class.php`.

Constantes en debut de classe :

```text
MODULE_NAME = 'fullcalendar'
MODULE_PICTO = 'fullcalendar@fullcalendar'
DOCUMENTATION_URL = ''
SUPPORT_URL = ''
```

Pseudo-code :

```text
class FullcalendarAboutPage
  render(moduleDescriptor):
    charger global langs
    construire un bloc HTML local :
      nom du module
      version
      compatibilite Dolibarr
      licence GPL
      eventuelle URL de documentation si constante non vide
    retourner le HTML
```

Modifier `admin/fullcalendar_about.php`.

Pseudo-code :

```text
charger main.inc.php
verifier admin
afficher entete Dolibarr
require class/FullcalendarAboutPage.class.php
require core/modules/modfullcalendar.class.php
moduleDescriptor = new modfullcalendar($db)
aboutPage = new FullcalendarAboutPage()
print aboutPage->render(moduleDescriptor)
```

Objectif :

- supprimer les appels reseau automatiques.
- ne plus dependre de `TechATM`.
- conserver une page about utile.

### 5. Langues et libelles

Modifier les traductions :

- Remplacer `ATMAbout` par une cle generique, par exemple `FullcalendarAbout`.
- Ajouter :
  - `FullcalendarAboutLicense`
  - `FullcalendarAboutVersion`
  - `FullcalendarAboutDocumentation`
  - `FullcalendarAboutNoExternalDependency`

Pseudo-code d'usage :

```text
langs->trans('FullcalendarAbout', version, dolibarr_min_version)
```

### 6. Nettoyage documentation

Modifier `README` et `ChangeLog.md` :

- retirer ABRICOT des prerequis.
- ajouter une entree de changelog : "Remove mandatory ABRICOT dependency".
- documenter que le module fonctionne avec Dolibarr natif.

## Base de donnees

Aucune table SQL a creer ou modifier.

Pas de migration de donnees a l'activation.

Pas de cle etrangere `fk_` a ajouter.

Pas de declaration `replaceThirdparty` a prevoir, car le module ne possede pas de table metier avec `fk_soc`. Les `fk_soc` identifies sont des champs natifs Dolibarr sur `actioncomm` et des parametres de filtrage/formulaire.

## Hooks et triggers

Hooks existants a conserver :

- `agenda`
- `agendalist`

Classe hook existante :

- `class/actions_fullcalendar.class.php`

Trigger existant :

- `core/triggers/interface_99_modfullcalendar_fullcalendartrigger.class.php`

Recommandation :

- Le trigger est un squelette demonstratif tres verbeux et sans effet metier. Il n'est pas lie a ABRICOT. Il peut etre conserve pour eviter un changement de comportement, mais une future passe de nettoyage pourrait le retirer ou le desactiver si aucun usage n'est attendu.

## Performance et volumetrie

Le decouplage ABRICOT n'ajoute pas de volume de donnees.

Verifier cependant les flux existants :

- `script/interface.php?get=events`
- `script/interface.php?get=tasks`
- filtres date debut/fin sur agenda.

Strategie :

- ne pas ajouter de requete supplementaire pendant le bootstrap.
- eviter tout appel HTTP externe en page admin.
- ne pas charger `TechATM` dans le descripteur module afin de garder la page des modules rapide et offline-compatible.

## Tests a prevoir

### Tests statiques

Commande :

```text
rg -n "abricot|inc.core|TPDOdb|TObjetStd|TFormCore|TListview|ATMdb" .
```

Attendu :

- aucune reference active a ABRICOT.
- references tolerees uniquement dans changelog historique ou documentation explicitement marquee legacy.

### Syntaxe PHP

Commandes :

```text
php -l config.default.php
php -l config.php
php -l core/modules/modfullcalendar.class.php
php -l admin/fullcalendar_about.php
php -l class/FullcalendarAboutPage.class.php
php -l script/create-maj-base.php
```

### Test d'activation

Dans un Dolibarr sans module ABRICOT :

```text
php -r "define('INC_FROM_DOLIBARR', true); require 'core/modules/modfullcalendar.class.php';"
```

Puis test manuel ou script Dolibarr :

```text
activer module fullcalendar
ouvrir /custom/fullcalendar/admin/fullcalendar_setup.php
ouvrir /custom/fullcalendar/task.php si FULLCALENDAR_ENABLE_TASKS=1
ouvrir /comm/action/index.php et verifier l'injection du calendrier
```

### Test fonctionnel AJAX

Verifier :

- chargement des evenements agenda.
- creation/modification d'un evenement.
- filtrage par tiers `fk_soc`.
- affichage des taches projet si active.

### Test de non-regression reseau

Verifier que l'ouverture de la page des modules et de la page about ne declenche plus d'appel vers `tech.atm-consulting.fr`.

## Risques

- `config.default.php` est utilise par `task.php` et `script/interface.php`; une erreur de chemin bloquerait tout le module.
- Des installations historiques peuvent s'attendre a voir la page about ATM distante. Le remplacement par une page locale change l'affichage mais pas le metier.
- Le module contient des assets tiers anciens (`fullcalendar`, `moment`) qui ne sont pas concernes par ABRICOT, mais ils restent un sujet de maintenance separe.

## Questions bloquantes avant implementation

Aucune question bloquante pour supprimer la dependance technique obligatoire a ABRICOT.

Questions non bloquantes a arbitrer avant merge :

- Souhaite-t-on conserver les mentions ATM dans les metadonnees (`editor_name`, `family`, images) ou rendre aussi le branding completement generique ?
- Souhaite-t-on supprimer physiquement `class/techatm.class.php` maintenant, ou le garder pour compatibilite et nettoyage ulterieur ?
- Faut-il conserver une URL de documentation officielle dans la page about locale ?

## Plan final d'implementation

1. Modifier le bootstrap pour supprimer l'appel obligatoire a ABRICOT.
2. Rendre l'activation du module native et supprimer l'appel au script de migration ABRICOT vide.
3. Transformer `script/create-maj-base.php` en no-op natif sans reference ABRICOT.
4. Ajouter une classe locale `FullcalendarAboutPage` et brancher `admin/fullcalendar_about.php` dessus.
5. Retirer `TechATM` du descripteur module et des flux actifs.
6. Mettre a jour les traductions et la documentation.
7. Executer les tests statiques, syntaxiques et fonctionnels listes ci-dessus.
