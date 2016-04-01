<?php
/**
 * The plugin class contains convenience functions for working with the
 * installation, upgrade, and removal of plugins from Zen Cart.
 *
 * To make use of this class the following file needs to be created:
 * <strong>/admin/includes/classes/<i>unique_key</i>_plugin.php</strong>:
 * A subclass of plugin (a class extending plugin). Any of the public methods
 * in the plugin class can be overridden and some must be overridden.
 *
 * The following optional files are recommended:
 * <strong>/admin/includes/init_includes/init_<i>unique_key</i>_install.php</strong>:
 * When called starts the install (loaded by the auto_loader).
 *
 * <strong>/admin/includes/init_includes/init_<i>unique_key</i>_remove.php</strong>:
 * When called starts the removal (loaded by the auto_loader).
 *
 * <strong>/admin/includes/auto_loaders/config.<i>unique_key</i>_onetime.php</strong>:
 * A file to start one of the init includes scripts (install / remove).
 *
 * @see plugin::createInitIncludeFile()
 * @see plugin::createOnetimeFile()
 * @copyright Copyright 2012 - 2014 Andrew Ballanger
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL V3.0
 */
class plugin {

	protected static $FILE_TYPE_BLANK = 0;
	protected static $FILE_TYPE_INIT = 1;
	protected static $FILE_TYPE_ONETIME = 2;

	/**
	 * Retrieves the version number of the plugin being installed, upgraded,
	 * or removed.
	 *
	 * Subclasses should override this method
	 * @return string the version
	 */
	public function getVersion() { return '0.1'; }

	/**
	 * Retrieves the unique key used as a prefix for configuration options
	 * stored in the database and as a prefix for language defines. The
	 * unique key should always start with a letter and all letters should
	 * be capital. The prefix should only include alpha numerical characters.
	 *
	 * Subclasses should override this method.
	 * @return string the unique key to use as a prefix
	 */
	public function getUniqueKey() { return 'PLUGIN'; }

	/**
	 * Retrieves the unique name displayed in the Zen Cart configuration menu
	 * and during installation, upgrade, or removal of the plugin.
	 *
	 * Subclasses should override this method.
	 * @return string the unique name for this plugin
	 */
	public function getUniqueName() { return 'plugin'; }

	/**
	 * Retrieves the description of this plugin.
	 *
	 * Subclasses can optionally override this method.
	 * @return string a short description of this plugin
	 */
	public function getDescription() { return ''; }

	/**
	 * Retrieves the default configuration options for this plugin.
	 *
	 * Subclasses can optionally override this method.
	 * @return multitype:(multitype:string)
	 */
	public function getDefaultConfiguration() { return array(); }

	/**
	 * Retrieves the admin menus for this plugin.
	 *
	 * Subclasses can optionally override this method.
	 * @return multitype:(multitype:string) the array of admin menus.
	 *
	 *		The following array keys are understood (+ indicates always required):
	 *		+	menu_key = The internal unique key for the page in the database.
	 *				If more than one page is desired, this can be used as a key
	 *				pointing to an array containg the corresponding keys below.
	 *
	 *				EX: array('pluginMenu1' => array(
	 *						'language_key' => 'BOX_CONFIGURATION_PLUGIN_MENU1'
	 *						)
	 *				);
	 *
	 *		+	language_key = The name displayed in the admin menu for the page.
	 *				This should be the name of a constant defined in a language file.
	 *
	 *			sort_order = The preferred location of the menu. This should be
	 * 				a non negative number. The lower the number, the higher up
	 * 				(to the left) the menu will appear. If two menus have the
	 *				same sort_order, they are listed in the order they were
	 * 				added Zen Cart. If not specified, the sort_order will be
	 * 				determined by taking the largest sort_order for any meny
	 * 				and adding one.
	 */
	public function getAdminMenus() { return array(); }

	/**
	 * Retrieves the admin pages for this plugin.
	 *
	 * Subclasses can optionally override this method.
	 * @return multitype:(multitype:string) the array of admin pages.
	 *
	 *		The following array keys are understood (+ indicates always required):
	 *		+	page_key = The internal unique key for the page in the database.
	 *				If more than one page is desired, this can be used as a key
	 *				pointing to an array containg the corresponding keys below.
	 *
	 *				EX: array('configPluginPage1' => array(
	 *						'language_key' => 'BOX_CONFIGURATION_PLUGIN_PAGE1'
	 *						)
	 *				);
	 *
	 *		+	language_key = The name displayed in the admin menu for the page.
	 *				This should be the name of a constant defined in a language file.
	 *
	 *			main_page = The name of a constant defined to represent the name
	 *				of the admin file loaded to display the page. If not defined
	 *				'FILENAME_CONFIGURATION' is used.
	 *
	 *			page_params = Any paramaters to send to the page. If the main page
	 *				is 'FILENAME_CONFIGURATION' the paramaters will be overwritten
	 *				with the correct paramaters.
	 *
	 *			menu_key = The name of the menu where this page should be attached.
	 *				This key is required unless main_page is 'FILENAME_CONFIGURATION'
	 *				or 'FILENAME_MODULES'.
	 *
	 *			display_on_menu = Indicates if this page should be displayed in
	 *				the admin menu. 'Y' for yes, 'N' otherwise. If not specified
	 *				this defaults to 'N' unless the main page is
	 *				'FILENAME_CONFIGURATION' (defaults to 'Y').
	 *
	 *			sort_order = The preferred location of the page in the menu. This
	 *				should be a non negative number. The lower the number, the
	 *				higher up in the menu the page will appear. If two pages on
	 *				the same menu have the same sort_order, they are listed in
	 *				the order they were added Zen Cart. If not specified, the
	 *				maximum sort_order used on the menu is determined and a number
	 *				one larger is used.
	 */
	public function getAdminPages() { return array(); }

	/**
	 * Retrieves the list of new files added by this plugin to Zen Cart.
	 * This should be an array of fully qualified file names.
	 *
	 * Subclasses should override this method.
	 * @return multitype:string
	 */
	public function getNewFiles() { return array(); }

	/**
	 * Retrieves the list of obsolete files added by previous versions of this
	 * plugin to Zen Cart. This should be an array of fully qualified file names.
	 *
	 * It is highly recommended to include "blank" versions of these files in
	 * your plugin distribution to overwrite the obsolete version and avoid
	 * potential conflicts if obsolete files are loaded by Zen Cart.
	 *
	 * Subclasses can optionally override this method.
	 * @return multitype:string
	 */
	public function getObsoleteFiles() { return array(); }

	/**
	 * Retrieves the list of core Zen Cart files modified by this plugin.
	 * This should be an array of fully qualified filenames containing
	 * an array of changes or checks to make against the file.
	 *
	 * To do search / replace the filename array will look like:
	 * 'filename' = array('search'=>'regular expression', 'replace'=>'replacement');
	 * The 'replacement' can include back references.
	 *
	 * To do a check the filename array will look like :
	 * 'filename' = array('check'=>'regular expression');
	 *
	 * Subclasses can optionally override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise
	 * @return multitype:multitype:string
	 */
	public function getModifiedCoreFiles($install = true) { return array(); }

	/**
	 * Handles any database changes required by this plugin.
	 *
	 * Subclasses can optionally override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	public function handleDatabaseChanges($install = true) { return true; }

	/**
	 * Handles any file changes required by this plugin. By default this will
	 * process file changes against getModifiedCoreFiles().
	 *
	 * Subclasses can optionally override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	public function handleFileChanges($install = true) { return $this->processFileChanges($install); }

	/**
	 * Handles the installation and removal of any new admin menus and pages
	 * required by this plugin. Pages will only be added if all of the menus
	 * were successfully added. Menus will only be removed if all of the menus
	 * were successfully removed.
	 *
	 * Subclasses can optionally override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	public function handleAdminMenuChanges($install = true) {
		$success = true;

		if($install) {
			$success = $this->processAdminMenus($install);
			if($success) $success = $this->processAdminPages($install);
		}
		else {
			$success = $this->processAdminPages($install);
			if($success) $success = $this->processAdminMenus($install);
		}

		return $success;
	}

	/**
	 * Handles any special "upgrade" changes needed from previously installed
	 * versions of the plugin.
	 *
	 * For example database changes or configuration key / value changes.
	 *
	 * Subclasses can optionally override this method.
	 * @param string $version
	 * @return boolean true if the upgrade is successful, false otherwise.
	 */
	public function handleUpgradeFrom($version) { return true; }

	/**
	 * Constructs a new plugin and loads necessary language files.
	 */
	public function __construct() {
		global $messageStack;

		// load the language files for the installer (fall back to english)
		$lang_file = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/plugin', '/plugin.php', 'false');
		if((include_once $lang_file) === false) {
			$lang_file = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/modules/plugin', '/plugin.php', 'false');
			if((include_once $lang_file) === false) {
				$messageStack->add('The core language file for installing, upgrading, or removing this plugin could not be loaded. Please check your upload!', 'error');
			}
		}

		// load any additional language files (fall back to english)
		$lang_file = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/plugin', '/' . strtolower($this->getUniqueKey()) . '.php', 'false');
		if((@include_once $lang_file) === false) {
			$lang_file = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/modules/plugin', '/' . strtolower($this->getUniqueKey()) . '.php', 'false');
			@include_once $lang_file;
		}

		// Display warning messages if anything in the admin configure.php
		// appears to be amiss (or just plain strange)
		$this->checkConfigure();
	}

	/**
	 * Installs (and upgrades) this plugin.
	 *
	 * Subclasses should NEVER override this method.
	 * @return boolean true if successful, false otherwise.
	 */
	public function install() {
		global $messageStack;

		// Verify new files have been uploaded
		$success = $this->verifyNewFiles();

		// Handle any files changes
		if($success) { $success = $this->handleFileChanges(); }

		// Perform an upgrade if needed
		if($success) { $success = $this->processUpgrade(); };

		// Install any needed tables
		if($success) $success = $this->handleDatabaseChanges();

		// Install the configuration group
		if($success) {
			$gid = $this->getConfigurationGroupId();
			if($gid == -1) $success = false;
		}

		// Install (or update) any needed configuration items
		if($success) $success = $this->updateConfiguration();

		// Install (or update) any admin menus and items
		if($success && function_exists('zen_register_admin_page')) {
			$success = $this->handleAdminMenuChanges();
			if(!$success) $messageStack->add(sprintf(
				PLUGIN_INSTALL_ERROR_AUTOLOAD,
				$this->getUniqueName()
			), 'error');
		}

		// Install (or update) the version number
		if($success) {
			$success = $this->updateVersionNumber();
		}

		$this->statusMessage($success);
		return $success;
	}

	/**
	 * Removes this plugin.
	 *
	 * Subclasses should NEVER override this method.
	 * @return boolean true if successful, false otherwise.
	 */
	public function remove() {

		// Remove installed tables
		$success = $this->handleDatabaseChanges(false);

		// Remove installed admin menus and items
		if($success && function_exists('zen_register_admin_page')) {
			$success = $this->handleAdminMenuChanges(false);
			if(!$success) $messageStack->add(sprintf(
				PLUGIN_REMOVE_ERROR_AUTOLOAD,
				$this->getUniqueName()
			), 'error');
		}

		// Remove installed configuration items
		if($success) $success = $this->removeConfiguration();

		// Remove
		if($success) $success = $this->handleFileChanges(false);

		// Remove any new files which have been uploaded
		if($success) $success = $this->removeNewFiles();

		// Remove the version number
		if($success) $success = $this->removeVersionNumber();

		$this->statusMessage($success, false);

		return $success;
	}

	/**
	 * Indicates if this module is installed.
	 *
	 * Subclasses should not override this method.
	 * @return boolean true if installed, false otherwise
	 */
	public function isInstalled() {
		return ($this->getInstalledVersion() !== '-1');
	}

	/**
	 * Retrieves the installed version of this plugin.
	 *
	 * By default the version is stored in the configuration table
	 * using "$this->getUniqueKey() . '_VERSION'".
	 *
	 * @return string the installed version, or '-1' if not installed.
	 */
	public function getInstalledVersion() {
		global $db;

		$retval = '-1';

		// Grab the currently installed version
		$check = $db->Execute(
			'SELECT `configuration_value` FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key` = \'' . $this->getUniqueKey() . '_VERSION\''
		);
		if(!$check->EOF) {
			$retval = $check->fields['configuration_value'];
		}

		return $retval;
	}

	/**
	 * Returns a list of the files utilized by the auto loader scripts for
	 * installing, upgrading, or removing this plugin.
	 *
	 * Subclasses should not override this method.
	 * @return multitype:string the locations of the files
	 */
	public function getAutoLoaderFiles() {
		return array(
			'class' => DIR_FS_ADMIN . DIR_WS_CLASSES .
				strtolower($this->getUniqueKey()) . '_plugin.php',
			'onetime' => DIR_FS_ADMIN . DIR_WS_INCLUDES . 'auto_loaders/config.' .
				strtolower($this->getUniqueKey()) . '_onetime.php',
			'install' => DIR_FS_ADMIN . DIR_WS_INCLUDES . 'init_includes/init_' .
				strtolower($this->getUniqueKey()) . '_install.php',
			'remove' => DIR_FS_ADMIN . DIR_WS_INCLUDES . 'init_includes/init_' .
				strtolower($this->getUniqueKey()) . '_remove.php'
		);
	}

	/**
	 * Creates a distribution zip file for this plugin. This packages all
	 * modified core files, new files, and installation code into one zip file.
	 *
	 * The files are pulled directly from the website using the information
	 * supplied by the plugin. If the information supplied is incomplete or the
	 * plugin is not fully installed (and working) the created distribution zip
	 * may be incomplete or be usable by others.
	 *
	 * Subclasses should not override this method.
	 * @param string $file the fully qualified filename of the file to create.
	 * @return boolean true if the zip file is created, false otherwise.
	 */
	public function createZipArchive($file) {
		global $messageStack;
		$success = true;

		// Make sure zip support is available
		if(!class_exists('ZipArchive')) {
			$messageStack->add(sprintf(ERROR_PLUGIN_NO_ZIPARCHIVE));
			return false;
		}

		// Make sure we can create the new distribution zip file
		$zip = new ZipArchive();
		if($zip->open($file, ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) !== true) {
			$messageStack->add(sprintf(ERROR_PLUGIN_CREATE_ZIP, $file));
			return false;
		}

		// Attempt to add files to the archive. Do not stop if an error occurs
		$success = $this->addModifiedCoreFilesToZipArchive($zip) && $success;
		$success = $this->addNewFilesToZipArchive($zip) && $success;
		$success = $this->addPluginCoreFilesToZipArchive($zip) && $success;
		$success = $this->addObsoleteFilesToZipArchive($zip) && $success;
		$success = $this->addAdditionalFilesToZipArchive($zip) && $success;
		$success = $this->addAutoLoaderFilesToZipArchive($zip) && $success;

		// Attempt to close / write the zip file if no errors occurred
		return $success && $zip->close();
	}

	/**
	 * Retrieves the paths used inside created Zip Archives for different
	 * types of files. If no type is specified an array of all types will be
	 * returned. If a type is unknown, an empty string will be returned.
	 *
	 * Known Types:
	 * 		modified = Core Zen Cart files modified by this plugin.
	 * 		new = New files added to Zen Cart by this plugin.
	 * 		core = Core Plugin files (required by this plugin).
	 * 		install = Files used to manually install this plugin.
	 *		remove = Files used to manually remove this plugin.
	 *
	 * @param string $type the type of file
	 * @return Ambigous <string>|string|multitype:string The path for the
	 * 		specified type or all known paths.
	 */
	protected function getZipArchivePath($type = null) {
		$paths = array(
			'modified' => '1_modified_core_files',
			'new' => '2_new_files',
			'core' => '2_new_files',
			'install' => '3_install',
			'remove' => '4_uninstall',
			'readme' => 'readme',
		);

		if($type !== null) {
			if(array_key_exists($type, $paths)) return $paths[$type];
			return '';
		}
		return $paths;
	}

	/**
	 * Add the modified core files to the zip archive. By default the files will
	 * be added to a folder named '1_modified_core_files'.
	 *
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addModifiedCoreFilesToZipArchive($zip) {
		$files = $this->getModifiedCoreFiles();
		foreach($files as $key => &$file) {
			if(is_array($file)) $file = $key;
		}
		return $this->addToZipArchive($zip, $this->getZipArchivePath('modified'), $files);
	}

	/**
	 * Add the new files to the zip archive. By default the files will
	 * be added to a folder named '2_new_files'.
	 *
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addNewFilesToZipArchive($zip) {
		return $this->addToZipArchive($zip, $this->getZipArchivePath('new'), $this->getNewFiles());
	}

	/**
	 * Add the plugin core files to the zip archive. By default the files will
	 * be added to a folder named '2_new_files'.
	 *
	 * These files are only needed if "Plugin Manager" is not already installed.
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addPluginCoreFilesToZipArchive($zip) {
		return $this->addToZipArchive($zip, $this->getZipArchivePath('core'), $this->getPluginCoreFiles());
	}

	/**
	 * Add obsolete files to the zip archive. The files added to the archive
	 * will be blank (to overwrite existing files when uploaded). When possible
	 * the files added to the archive will contain a comment about why the file
	 * is blank. By default the files will be added to a folder named
	 * '2_new_files'.
	 *
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addObsoleteFilesToZipArchive($zip) {
		return $this->addToZipArchive($zip, $this->getZipArchivePath('new'), $this->getObsoleteFiles(), true);
	}

	/**
	 * Add additional files to the zip archive.
	 *
	 * Subclasses can optionally override this method.
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addAdditionalFilesToZipArchive($zip) { return true; }

	/**
	 * Add files to the zip archive required to install this module when the
	 * "Plugin Manager" has not been installed. These files can be uploaded
	 * manually via FTP to trigger the installation, upgrade, or removal of this
	 * plugin.
	 *
	 * This function may be removed in the future.
	 * @param ZipArchive $zip an archive (already opened for writing).
	 * @return boolean true if the files were added, false otherwise.
	 */
	protected function addAutoLoaderFilesToZipArchive($zip) {
		$success = true;
		$autoloaderFiles = $this->getAutoLoaderFiles();

		$local = $this->getZipArchivePath('install') . '/your_admin_folder/' . substr($autoloaderFiles['install'], strlen(DIR_FS_ADMIN));
		$contents = $this->createFileContents(plugin::$FILE_TYPE_INIT, $local, true);
		if(!$zip->addFromString($local, $contents)) {
			$success = false;
		}

		$local = $this->getZipArchivePath('remove') . '/your_admin_folder/' . substr($autoloaderFiles['remove'], strlen(DIR_FS_ADMIN));
		$contents = $this->createFileContents(plugin::$FILE_TYPE_INIT, $local, false);
		if(!$zip->addFromString($local, $contents)) {
			$success = false;
		}

		$local = $this->getZipArchivePath('install') . '/your_admin_folder/' . substr($autoloaderFiles['onetime'], strlen(DIR_FS_ADMIN));
		$contents = $this->createFileContents(plugin::$FILE_TYPE_ONETIME, $local, true);
		if(!$zip->addFromString($local, $contents)) {
			$success = false;
		}

		$local = $this->getZipArchivePath('remove') . '/your_admin_folder/' . substr($autoloaderFiles['onetime'], strlen(DIR_FS_ADMIN));
		$contents = $this->createFileContents(plugin::$FILE_TYPE_ONETIME, $local, false);
		if(!$zip->addFromString($local, $contents)) {
			$success = false;
		}

		return $success;
	}

	/**
	 * Verifies the new files required by this module are uploaded. This will
	 * automatically check for the presence of required installation and removal
	 * files.
	 *
	 * @see plugin::getNewFiles()
	 * @return boolean true if the verification passes, false otherwise.
	 */
	protected function verifyNewFiles() {
		global $messageStack;

		$success = true;

		// Verify new files
		foreach($this->getNewFiles() as $file) {
			if(!file_exists($file)) {
				$messageStack->add(sprintf(PLUGIN_INSTALL_ERROR_FILE_NOT_FOUND, $this->getUniqueName(), $file), 'error');
				$success = false;
			}
		}

		return $success;
	}

	/**
	 * Removes the new files installed by this module.
	 *
	 * @see plugin::getNewFiles()
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function removeNewFiles() {
		global $messageStack;

		$success = true;
		foreach($this->getNewFiles() as $file) {
			@unlink($file);

			if(file_exists($file)) {
				$messageStack->add(sprintf(PLUGIN_REMOVE_ERROR_FILE_FOUND, $this->getUniqueName(), $file), 'error');
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Removes the obsolete files installed by previous versions of this module.
	 *
	 * @see plugin::getObsoleteFiles()
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function removeObsoleteFiles() {
		global $messageStack;

		$success = true;
		foreach($this->getObsoleteFiles() as $file) {
			// Remove this file if it exists (should no longer be used)
			if(file_exists($file) && !unlink($file)) {
				$messageStack->add(
					sprintf(
						PLUGIN_INSTALL_ERROR_FILE_FOUND,
						$this->getUniqueName(),
						$file
					),
					'error'
				);
				$success = false;
			}
		}
		return $success;
	}

	/**
	 * Processes any changes to files required by this plugin.
	 *
	 * Subclasses should generally not override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise.
	 * @param array $files an array of fully qualified filenames changed.
	 *		Each filename can optionally specify an array of checks or
	 *		changes (seach/replace) to make against the file.
	 *
	 *		To do a check the filename array will look like :
	 *		'filename' = array('check'=>'regular expression');
	 *
	 *		To do search / replace the filename array will look like:
	 *		'filename' = array('search'=>'regular expression', 'replace'=>'replacement');
	 *		The 'replacement' can include back references.
	 *
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	protected function processFileChanges($install = true, $files = null) {
		$success = true;
		if($files === null || !is_array($files)) $files = $this->getModifiedCoreFiles($install);

		foreach($files as $file => $changes) {
			// Attempt to upgrade files if search / replace keys exist
			if(!is_array($changes)) {
				// Only notified the core file was changed
			}
			else if(array_key_exists('check', $changes)) {
				// Provided with information to check if the file has been altered
				$success = $success && $this->checkInFile($file, $changes['check'], $changes['replace']);
			}
			else if(array_key_exists('search', $changes) && array_key_exists('replace', $changes)) {
				// Provided with search/replace information to alter files directly
				$success = $success && $this->replaceInFile($file, $changes['search'], $changes['replace']);
			}
		}

		return $success;
	}

	/**
	 * Processes any changes to the admin menus required by this plugin.
	 *
	 * Subclasses should generally not override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise.
	 * @param array $menus an array containing the admin menu(s).
	 *
	 *		The following array keys are understood (+ indicates always required):
	 *		+	menu_key = The internal unique key for the page in the database.
	 *				If more than one page is desired, this can be used as a key
	 *				pointing to an array containg the corresponding keys below.
	 *
	 *				EX: array('pluginMenu1' => array(
	 *						'language_key' => 'BOX_CONFIGURATION_PLUGIN_MENU1'
	 *						)
	 *				);
	 *
	 *		+	language_key = The name displayed in the admin menu for the page.
	 *				This should be the name of a constant defined in a language file.
	 *
	 *			sort_order = The preferred location of the menu. This should be
	 * 				a non negative number. The lower the number, the higher up
	 * 				(to the left) the menu will appear. If two menus have the
	 *				same sort_order, they are listed in the order they were
	 * 				added Zen Cart. If not specified, the sort_order will be
	 * 				determined by taking the largest sort_order for any meny
	 * 				and adding one.
	 *
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	protected function processAdminMenus($install = true, $menus = null) {
		global $db, $messageStack;

		if($menus === null || !is_array($menus)) $menus = $this->getAdminMenus();
		if(array_key_exists('menu_key', $menus)) {
			$menu_key = $menus['menu_key'];
			unset($menus['menu_key']);
			$menus = array(
				$menu_key => $menus
			);
			unset($menu_key);
		}

		$success = true;
		foreach($menus as $key => $menu) {

			// Validate core keys exist
			if(!zen_not_null($key) || !array_key_exists('language_key', $menu)) {
				$messageStack->add(
					sprintf(
						PLUGIN_INTERNAL_FORMAT_ADMIN_MENU_KEY,
						$this->getUniqueName()
					),
					'error'
				);
				return false;
			}

			// Not using menu directly incase it contains extra columns
			$sql_data_array = array(
				'language_key' => $menu['language_key']
			);

			// Does the menu already exist?
			$check = $db->Execute(
				'SELECT `menu_key`, `language_key`, `sort_order` FROM `' . TABLE_ADMIN_MENUS . '` ' .
				'WHERE `menu_key` = \'' . zen_db_prepare_input($key) . '\''
			);

			if(install) {
				if(!$check->EOF) {

					// Menu already exists, cleanup data
					if(!array_key_exists('sort_order', $menu)) {
						$sql_data_array['sort_order'] = (int)$menu['sort_order'];
					}
					$sql_data_array = array_merge($check->fields, $sql_data_array);
					unset($sql_data_array['menu_key']);

					// Save the changes
					zen_db_perform(TABLE_ADMIN_MENUS, $sql_data_array, 'update', '`menu_key` = \'' . zen_db_prepare_input($key) . '\'');
				}
				else {
					// Menu does not exist, cleanup data
					$sql_data_array['menu_key'] = $key;
					if(!array_key_exists('sort_order', $sql_data_array)) {
						$menu_sort = $db->Execute(
							'SELECT MAX(sort_order) as max_sort FROM `' . TABLE_ADMIN_MENUS . '`'
						);
						if(!$menu_sort->EOF) {
							$sql_data_array['sort_order'] = (int) $page_sort->fields['max_sort'] + 1;
						}
						else {
							$messageStack->add(sprintf(PLUGIN_INSTALL_ERROR_SORT_ORDER, TABLE_ADMIN_MENUS), 'warning');
							$sql_data_array['sort_order'] = 0;
						}
						unset($menu_sort);
					}
					if($sql_data_array['sort_order'] < 0) $sql_data_array['sort_order'] = 0;

					zen_db_perform(TABLE_ADMIN_MENUS, $sql_data_array);
				}

				$verify = $db->Execute(
					'SELECT `menu_key` FROM `' . TABLE_ADMIN_MENUS . '` ' .
					'WHERE `menu_key` = \'' . zen_db_prepare_input($key) . '\''
				);
				if($verify->EOF) {
					$messageStack->add(
						sprintf(
							PLUGIN_INSTALL_ERROR_DATABASE_KEY,
							$this->getUniqueName(),
							TABLE_ADMIN_MENUS,
							'menu_key: ' . $key
						),
						'error'
					);
					$success = false;
				}
			}
			else if(!$check->EOF) {
				$db->Execute(
					'DELETE FROM `' . TABLE_ADMIN_MENUS . '` WHERE `menu_key` = \'' . zen_db_prepare_input($key) . '\''
				);

				$verify = $db->Execute(
					'SELECT `menu_key` FROM `' . TABLE_ADMIN_MENUS . '` ' .
					'WHERE `menu_key` = \'' . zen_db_prepare_input($key) . '\''
				);
				if(!$verify->EOF) {
					$messageStack->add(
						sprintf(
							PLUGIN_REMOVE_ERROR_DATABASE_KEY,
							$this->getUniqueName(),
							TABLE_ADMIN_MENUS,
							'menu_key: ' . $key
						),
						'error'
					);
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Processes any changes to the admin pages required by this plugin.
	 *
	 * Subclasses should generally not override this method.
	 * @param boolean $install true if installing / upgrading, false otherwise.
	 * @param array $pages an array containing the admin page(s).
	 *
	 *		The following array keys are understood (+ indicates always required):
	 *		+	page_key = The internal unique key for the page in the database.
	 *				If more than one page is desired, this can be used as a key
	 *				pointing to an array containg the corresponding keys below.
	 *
	 *				EX: array('configPluginPage1' => array(
	 *						'language_key' => 'BOX_CONFIGURATION_PLUGIN_PAGE1'
	 *						)
	 *				);
	 *
	 *		+	language_key = The name displayed in the admin menu for the page.
	 *				This should be the name of a constant defined in a language file.
	 *
	 *			main_page = The name of a constant defined to represent the name
	 *				of the admin file loaded to display the page. If not defined
	 *				'FILENAME_CONFIGURATION' is used.
	 *
	 *			page_params = Any paramaters to send to the page. If the main page
	 *				is 'FILENAME_CONFIGURATION' the paramaters will be overwritten
	 *				with the correct paramaters.
	 *
	 *			menu_key = The name of the menu where this page should be attached.
	 *				This key is required unless main_page is 'FILENAME_CONFIGURATION'
	 *				or 'FILENAME_MODULES'.
	 *
	 *			display_on_menu = Indicates if this page should be displayed in
	 *				the admin menu. 'Y' for yes, 'N' otherwise. If not specified
	 *				this defaults to 'N' unless the main page is
	 *				'FILENAME_CONFIGURATION' (defaults to 'Y').
	 *
	 *			sort_order = The preffered location of the page in the menu. This
	 *				should be a non negative number. The lower the number, the
	 *				higher up in the menu the page will appear. If two pages on
	 *				the same menu have the same sort_order, they are listed in
	 *				the order they were added Zen Cart. If not specified, the
	 *				maximum sort_order used on the menu is determined and a number
	 *				one larger is used.
	 *
	 * @return boolean true if the changes succeed, false otherwise.
	 */
	protected function processAdminPages($install = true, $pages = null) {
		global $db, $messageStack;

		if($pages === null || !is_array($pages)) $pages = $this->getAdminPages();
		if(array_key_exists('page_key', $pages)) {
			$page_key = $pages['page_key'];
			unset($pages['page_key']);
			$pages = array(
				$page_key => $pages
			);
			unset($page_key);
		}

		$success = true;
		foreach($pages as $key => $page) {

			// Validate core keys exist
			if(!zen_not_null($key) || !array_key_exists('language_key', $page)) {
				$messageStack->add(
					sprintf(
						PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_KEY,
						$this->getUniqueName()
					),
					'error'
				);
				return false;
			}

			// Configure defaults
			if(!array_key_exists('main_page', $page)) $page['main_page'] = 'FILENAME_CONFIGURATION';
			switch($page['main_page']) {
				case 'FILENAME_CONFIGURATION':
					// Always overwrite with the correct data
					$page['page_params'] = 'gID=' . $this->getConfigurationGroupId();
					break;
				default:
					if(!array_key_exists('page_params', $page)) $page['page_params'] = '';
			}
			if(!array_key_exists('menu_key', $page)) {
				switch($page['main_page']) {
					case 'FILENAME_CONFIGURATION':
						$page['menu_key'] = 'configuration';
						break;
					case 'FILENAME_MODULES':
						$page['menu_key'] = 'modules';
						break;
					default:
						$messageStack->add(
							sprintf(
								PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY,
								$this->getUniqueName()
							),
							'error'
						);
						return false;
				}
			}

			// Process / Verify the Admin Page
			if($install) {
				if(!zen_page_key_exists($key)) {
					// Validate the menu_key exists in the database.
					$check = $db->Execute(
							'SELECT `menu_key` FROM `' . TABLE_ADMIN_MENUS . '` ' .
							'WHERE `menu_key` = \'' . zen_db_prepare_input($page['menu_key']) . '\''
					);
					if($check->EOF) {
						$std_menu_keys = array(
								'configuration', 'catalog', 'modules', 'customers',
								'taxes', 'localization', 'reports', 'tools', 'gv',
								'access', 'extras'
						);

						$messageStack->add(
								sprintf(
										(in_array($page['menu_key'], $std_menu_keys) ? PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_BROKEN : PLUGIN_INTERNAL_FORMAT_ADMIN_PAGE_MENU_KEY_MISSING),
										$this->getUniqueName(),
										$page['menu_key']
								),
								'error'
						);
						return false;
					}

					if(!array_key_exists('display_on_menu', $page)) {
						if($page['main_page'] == 'FILENAME_CONFIGURATION') {
							$page['display_on_menu'] = 'Y';
						}
						else {
							$page['display_on_menu'] = 'N';
						}
					}
					if(!array_key_exists('sort_order', $page)) {
						$page_sort = $db->Execute(
								'SELECT MAX(sort_order) as max_sort FROM `' . TABLE_ADMIN_PAGES . '` ' .
								'WHERE `menu_key`=\''. zen_db_prepare_input($page['menu_key']) . '\''
						);
						if(!$page_sort->EOF) {
							$page['sort_order'] = (int)$page_sort->fields['max_sort'] + 1;
						}
						else {
							$messageStack->add(sprintf(PLUGIN_INSTALL_ERROR_SORT_ORDER, TABLE_ADMIN_PAGES), 'warning');
							$page['sort_order'] = 0;
						}
						unset($page_sort);
					}
					if((int)$page['sort_order'] < 0) $page['sort_order'] = 0;

					zen_register_admin_page(
						$key, $page['language_key'], $page['main_page'],
						$page['page_params'], $page['menu_key'],
						$page['display_on_menu'], $page['sort_order']
					);
					if(!zen_page_key_exists($key)) $success = false;
				}
			}
			else if(zen_page_key_exists($key)) {
				zen_deregister_admin_pages($key);
				if(zen_page_key_exists($key)) $success = false;
			}
		}

		return $success;
	}

	/**
	 * Processes an upgrade (if needed) of this plugin.
	 *
	 * @return boolean true if successful, false otherwise.
	 */
	protected function processUpgrade() {
		global $messageStack;

		$success = $this->removeObsoleteFiles();

		$version = $this->getInstalledVersion();
		if($version !== '-1') {
			$messageStack->add(sprintf(
				PLUGIN_INSTALL_FOUND_PREVIOUS_VERSION,
				$this->getUniqueName(),
				$version,
				$this->getVersion()
			), 'success');

			return $success && $this->handleUpgradeFrom($version);
		}

		return $success;
	}

	/**
	 * Removes the version number of this plugin from the database.
	 *
	 * By default the version is stored in the configuration table
	 * using "$this->getUniqueKey() . '_VERSION'".
	 *
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function removeVersionNumber() {
		global $db;

		$success = true;

		$db->Execute(
			'DELETE FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key`=\'' . $this->getUniqueKey() . '_VERSION\''
		);
		$check = $db->Execute(
			'SELECT `configuration_value` FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key` = \'' . $this->getUniqueKey() . '_VERSION\''
		);
		if(!$check->EOF) {
			$messageStack->add(
				sprintf(
					PLUGIN_REMOVE_ERROR_DATABASE_TABLE,
					$this->getUniqueName(),
					TABLE_CONFIGURATION,
					'configuration_key: ' . $key
				),
				'error'
			);
			$success = false;
		}

		return $success;
	}

	/**
	 * Retrieves the configuration group id for this plugin based upon
	 * the unique name of the plugin.
	 *
	 * If the configuration group id cannot be found in the database, the
	 * configuration group id will be added to the database.
	 *
	 * @return integer the configuration group id, or -1 if it cannot be added
	 */
	protected function getConfigurationGroupId() {
		global $db, $messageStack;

		$retval = -1;

		if($this->getUniqueName() === null) return $retval;

		$check = $db->Execute(
			'SELECT `configuration_group_id` FROM `' . TABLE_CONFIGURATION_GROUP . '` ' .
			'WHERE `configuration_group_title` = \'' . $this->getUniqueName() . '\''
		);
		if($check->EOF) {
			$max_sort = $db->Execute(
				'SELECT MAX(sort_order) AS `max_sort` FROM `' . TABLE_CONFIGURATION_GROUP . '`'
			);
			if(!$max_sort->EOF) {
				$sql_data_array = array(
					'configuration_group_title' => $this->getUniqueName(),
					'configuration_group_description' => sprintf(PLUGIN_CONFIG_GROUP_DESCRIPTION, $this->getUniqueName()),
					'sort_order' => $max_sort->fields['max_sort'] + 1,
					'visible' => 1
				);
				zen_db_perform(TABLE_CONFIGURATION_GROUP, $sql_data_array);
				$retval = zen_db_insert_id();
				if($retval === false || $retval == 0) $retval = -1;
			}
			else {
				$messageStack->add(sprintf(PLUGIN_INSTALL_ERROR_SORT_ORDER, $this->getUniqueName(), TABLE_CONFIGURATION_GROUP), 'error');
			}
		}
		else {
			$retval = (int) $check->fields['configuration_group_id'];
		}

		if($retval == -1) {
			$messageStack->add(
				sprintf(
					PLUGIN_INSTALL_ERROR_DATABASE_KEY,
					$this->getUniqueName(),
					TABLE_CONFIGURATION_GROUP,
					'configuration_group_title: ' . $this->getUniqueName()
				),
				'error'
			);
		}

		return $retval;
	}

	/**
	 * Updates the configuration options stored in the database for this plugin
	 * using the default configuration.
	 *
	 * This will update all portions of an option except the "key" and "value".
	 * If the configuration option does not exist it will be added with the
	 * specified default value.
	 *
	 * @see plugin::getDefaultConfiguration()
	 * @return boolean true if the update is successful, false otherwise.
	 */
	protected function updateConfiguration() {
		global $db;

		// Some default data used for configuration entries
		$sql_data_array = array(
			'configuration_group_id' => $this->getConfigurationGroupId(),
			'sort_order' => 1, // Start at 1, version is at 0
			'last_modified' => 'null',
			'date_added' => 'now()'
		);

		foreach($this->getDefaultConfiguration() as $key => $data) {
			// Add the unique key as a prefix
			$key = $this->getUniqueKey() . '_' . $key;

			// Checks presence in database (do not trust it to be defined)
			$check = $db->Execute(
				'SELECT `configuration_id` FROM `' . TABLE_CONFIGURATION . '` ' .
				'WHERE `configuration_key`=\'' . $key . '\''
			);
			if($check->EOF) {
				$data['configuration_key'] = $key;
				$data['configuration_title'] = constant($key . '_TITLE');
				$data['configuration_description'] = constant($key . '_DESCRIPTION');
				zen_db_perform(TABLE_CONFIGURATION, array_merge($sql_data_array, $data));
			}
			else {
				unset($data['configuration_value']);
				$data['sort_order'] = $sql_data_array['sort_order'];
				$this->updateConfigurationOption($key, $data);
			}

			$sql_data_array['sort_order']++;
		}

		return true;
	}

	/**
	 * Removes the configuration entries from the database.
	 *
	 * @see plugin::getDefaultConfiguration()
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function removeConfiguration() {
		global $db, $messageStack;

		$success = true;

		// Remove default configuration items
		foreach($this->getDefaultConfiguration() as $key => $data) {
			$key = $this->getUniqueKey() . '_' . $key;
			if(!$this->removeConfigurationOption($key)) {
				$messageStack->add(
					sprintf(
						PLUGIN_REMOVE_ERROR_DATABASE_KEY,
						$this->getUniqueName(),
						TABLE_CONFIGURATION,
						'configuration_key: ' . $key
					),
					'error'
				);
				$success = false;
			}
		}

		// Remove the configuration group id
		$db->Execute(
			'DELETE FROM `' . TABLE_CONFIGURATION_GROUP . '` ' .
			'WHERE `configuration_group_title`=\'' . $this->getUniqueName() . '\''
		);
		$check = $db->Execute(
			'SELECT `configuration_group_id` FROM `' . TABLE_CONFIGURATION_GROUP . '` ' .
			'WHERE `configuration_group_title`=\'' . $this->getUniqueName() . '\''
		);
		if(!$check->EOF) {
			$messageStack->add(
				sprintf(
					PLUGIN_REMOVE_ERROR_DATABASE_KEY,
					$this->getUniqueName(),
					TABLE_CONFIGURATION_GROUP,
					'configuration_group_title: ' . $this->getUniqueName()
				),
				'error'
			);
			$success = false;
		}

		return $success;
	}

	/**
	 * Updates the version number of the plugin in the database.
	 * If not already present the version will be added.
	 *
	 * By default the version is stored in the configuration table
	 * using "$this->getUniqueKey() . '_VERSION'".
	 *
	 * @return boolean true if the update is successful, false otherwise.
	 */
	protected function updateVersionNumber($sort_order = 0) {
		global $db;

		$success = true;
		$key = $this->getUniqueKey() . '_VERSION';

		// Some default data used for configuration entries
		$sql_data_array = array(
			'configuration_group_id' => $this->getConfigurationGroupId(),
			'sort_order' => $sort_order,
			'configuration_key' => $key,
			'configuration_title' => sprintf(PLUGIN_OPTION_VERSION_TITLE, $this->getUniqueName()),
			'configuration_description' => sprintf(PLUGIN_OPTION_VERSION_DESCRIPTION, $this->getUniqueName()),
			'configuration_value' => $this->getVersion(),
			'set_function' => 'zen_cfg_select_option(array(\'' . $this->getVersion() . '\'),'
		);

		$check = $db->Execute(
			'SELECT `configuration_id` FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key` = \'' . $key . '\''
		);
		if($check->EOF) {
			$sql_data_array['date_added'] = 'now()';
			zen_db_perform(TABLE_CONFIGURATION, $sql_data_array);
		}
		else {
			$sql_data_array['last_modified'] = 'now()';
			zen_db_perform(
				TABLE_CONFIGURATION, $sql_data_array, 'update',
				'`configuration_id`=\'' . $check->fields['configuration_id'] . '\''
			);
		}
		unset($sql_data_array);

		$check = $db->Execute(
			'SELECT `configuration_id` FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key` = \'' . $key . '\''
		);
		if($check->EOF) {
			$messageStack->add(
				sprintf(
					PLUGIN_INSTALL_ERROR_DATABASE_KEY,
					$this->getUniqueName(),
					TABLE_CONFIGURATION,
					'configuration_key: ' . $key
				),
				'error'
			);
			$success = false;
		}

		return $success;
	}

	/**
	 * Displays a status message (success / failure) when the script ends.
	 * If successful this also disables the script from running again.
	 *
	 * @param boolean $success the current status of the script
	 * @param boolean $install true if installing / upgrading, false otherwise
	 */
	protected function statusMessage($success = true, $install = true) {
		global $messageStack;

		$formatPrefix = 'PLUGIN_' . ($install ? 'INSTALL' : 'REMOVE') . '_';
		if($success) {
			$files = $this->getAutoLoaderFiles();

			// Verify we have permission to modify the files
			foreach($files as $key => $file) {
				if(!is_writable($file) && file_exists($file)) {
					$messageStack->add(
						sprintf(
							constant($formatPrefix . 'ERROR_FILE_WRITE'),
							$this->getUniqueName(),
							$file
						),
						'error'
					);
					$success = false;
				}
			}

			// Try to remove the files
			if($success) {
				if($install) {
					if(!@unlink($files['onetime']) && file_exists($files['onetime'])) {
						$messageStack->add(
							sprintf(
								constant($formatPrefix . 'ERROR_AUTOLOAD'),
								$this->getUniqueName(),
								$files['onetime']
							),
							'error'
						);
						$success = false;
					}
				}
				else {
					foreach($files as $key => $file) {
						if(!@unlink($file) && file_exists($file)) {
							$messageStack->add(
								sprintf(
									constant($formatPrefix . 'ERROR_AUTOLOAD'),
									$this->getUniqueName(),
									$file
								),
								'error'
							);
							$success = false;
						}
					}

					// Remove any plugin specific installer language files.
					// (these may not be present so ignore errors)
					@unlink(zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/plugin', '/' . strtolower($this->getUniqueKey()) . '.php', 'false'));
					@unlink(zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . 'english/modules/plugin', '/' . strtolower($this->getUniqueKey()) . '.php', 'false'));
				}
			}
		}

		// Display message
		$messageStack->add(
			sprintf(
				constant($formatPrefix . ($success ? 'SUCCESS' : 'ERROR')),
				$this->getUniqueName()
			),
			($success ? 'success' : 'error')
		);

		return $success;
	}

	/**
	 * Determines if the table exists in the database.
	 *
	 * @param string $table the name of the table in the database
	 * @return boolean true if the table exists, false otherwise
	 */
	protected function dbTableExists($table) {
		global $db;

		$check = $db->Execute(
			'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ' .
			'WHERE TABLE_SCHEMA = \'' . DB_DATABASE . '\' ' .
			'AND TABLE_NAME = \'' . $db->prepare_input($table) . '\''
		);
		return !$check->EOF;
	}

	/**
	 * Determines if the column exists in the table in the database.
	 *
	 * @param string $table the name of the table in the database
	 * @param string $column the name of the column in the table
	 * @return boolean true if the column exists in the table, false otherwise
	 */
	protected function dbColumnExists($table, $column) {
		global $db;

		$check = $db->Execute(
			'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS ' .
			'WHERE TABLE_SCHEMA = \'' . DB_DATABASE . '\' ' .
			'AND TABLE_NAME = \'' . $db->prepare_input($table) . '\' ' .
			'AND COLUMN_NAME = \''. $db->prepare_input($column) . '\''
		);
		return !$check->EOF;
	}

	/**
	 * Determines if the key exists in the table in the database.
	 *
	 * @param string $table the name of the table in the database
	 * @param string $key the name of the key in the table
	 * @return boolean true if the key exists in the table, false otherwise
	 */
	protected function dbKeyExists($table, $key) {
		global $db;

		$check = $db->Execute(
			'SHOW KEYS FROM `' . $db->prepare_input($table) . '` ' .
			'WHERE `key_name`=\'' . $db->prepare_input($key) . '\''
		);
		return !$check->EOF;
	}

	/**
	 * Updates the configuration option in the database using the supplied
	 * configuration data. If the configuration option does not exist this method
	 * will do nothing. A new key to use for the option can be specified in the
	 * data array (using 'configuration_key'). If not specified in the data array
	 * the title and description for the option will be updated using the defined
	 * language constants for the key.
	 *
	 * The language constants for the key are determined by taking the key and
	 * appending _TITLE and _DESCRIPTION respectively.
	 *
	 * @param string $key the configuration key to update.
	 * @param array $data the array of configuration settings.
	 */
	protected function updateConfigurationOption($key, $data = array()) {
		global $db;

		// If a new key was sent, make sure we use the new key
		$new_key = (array_key_exists('configuration_key', $data) ? $data['configuration_key'] : $key);

		$check = $db->Execute(
			'SELECT `configuration_id` FROM `' . TABLE_CONFIGURATION . '` ' .
			'WHERE `configuration_key`=\'' . $key . '\''
		);
		if(!$check->EOF) {
			$sql_data_array = array(
				'configuration_key' => $new_key,
				'configuration_title' => @constant($new_key . '_TITLE'),
				'configuration_description' => @constant($new_key . '_DESCRIPTION'),
				'last_modified' => 'now()'
			);

			zen_db_perform(TABLE_CONFIGURATION, array_merge($sql_data_array, $data), 'update', '`configuration_id`=\'' . $check->fields['configuration_id'] .'\'');
		}
	}

	/**
	 * Removes a specific configuration option if present.
	 *
	 * @param string $key the configuration key to update.
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function removeConfigurationOption($key) {
		global $db;

		if(zen_not_null($key) && defined($key)) {
			$db->Execute(
				'DELETE FROM `' . TABLE_CONFIGURATION . '` ' .
				'WHERE `configuration_key`=\'' . $key . '\''
			);
			$check = $db->Execute(
				'SELECT `configuration_id` FROM `' . TABLE_CONFIGURATION . '` ' .
				'WHERE `configuration_key` = \'' . $key . '\''
			);
			if(!$check->EOF) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Checks the configure.php files for some common errors which can adversely
	 * affect Zen Cart and modifications made to Zen Cart.
	 *
	 * By default a warning will be displayed for any potential issues using
	 * Zen Cart's message stack (will appear once at the top of the admin).
	 *
	 * @param boolean $silent indicates if we should be silent or report warnings.
	 * @return boolean true if the removal is successful, false otherwise.
	 */
	protected function checkConfigure($silent = false) {
		global $messageStack;

		$success = true;
		$warnings = array();

		// First check the pre-loaded admin variables (from '/admin/includes/configure.php')
		$warnings = array_merge($warnings, $this->checkConfigureServerSetting('HTTP_CATALOG_SERVER', HTTP_CATALOG_SERVER, 'DIR_WS_CATALOG'));
		$warnings = array_merge($warnings, $this->checkConfigureServerSetting('HTTPS_CATALOG_SERVER', HTTPS_CATALOG_SERVER, 'DIR_WS_HTTPS_CATALOG'));
		$warnings = array_merge($warnings, $this->checkConfigurePathSetting('DIR_WS_CATALOG', DIR_WS_CATALOG));
		$warnings = array_merge($warnings, $this->checkConfigurePathSetting('DIR_WS_HTTPS_CATALOG', DIR_WS_HTTPS_CATALOG));

		if(count($warnings) > 0) {
			$success = false;

			// build message
			$message = sprintf(
				PLUGIN_CONFIG_ADMIN_WARNINGS,
				DIR_FS_ADMIN . DIR_WS_INCLUDES . 'configure.php'
			) . '<ul>';
			foreach($warnings as $warning) {
				$message .= '<li>' . $warning . '</li>';
			}
			$message .= '</ul>';
			$warnings = array();

			$messageStack->add($message, 'warning');
		}

		// Try to extract and check the store variables (from '/includes/configure.php')
		$store_config_file = DIR_FS_CATALOG . DIR_WS_INCLUDES . 'configure.php';
		$store_config_contents = @file_get_contents($store_config_file);
		if($store_config_contents === false) {
			$success = false;

			$messageStack->add(
				sprintf(
					PLUGIN_CONFIG_STORE_CONFIGURE,
					$store_config_file,
					'DIR_FS_CATALOG + DIR_WS_INCLUDES'
				),
				'warning'
			);
		}
		else {
			$warnings = array_merge($warnings, $this->checkConfigureServerSetting(
				'HTTP_SERVER',
				$this->getConfiguredDefine('HTTP_SERVER', $store_config_file, $store_config_contents),
				'DIR_WS_CATALOG'
			));
			$warnings = array_merge($warnings, $this->checkConfigureServerSetting(
				'HTTPS_SERVER',
				$this->getConfiguredDefine('HTTPS_SERVER', $store_config_file, $store_config_contents),
				'DIR_WS_HTTPS_CATALOG'
			));
			$warnings = array_merge($warnings, $this->checkConfigurePathSetting(
				'DIR_WS_CATALOG',
				$this->getConfiguredDefine('DIR_WS_CATALOG', $store_config_file, $store_config_contents)
			));
			$warnings = array_merge($warnings, $this->checkConfigurePathSetting(
				'DIR_WS_HTTPS_CATALOG',
				$this->getConfiguredDefine('DIR_WS_HTTPS_CATALOG', $store_config_file, $store_config_contents)
			));

			if(count($warnings) > 0) {
				$success = false;

				// build message
				$message = sprintf(
					PLUGIN_CONFIG_STORE_WARNINGS,
					DIR_FS_CATALOG . DIR_WS_INCLUDES . 'configure.php'
				) . '<ul>';
				foreach($warnings as $warning) {
					$message .= '<li>' . $warning . '</li>';
				}
				$message .= '</ul>';
				$warnings = array();

				$messageStack->add($message, 'warning');
			}
		}

		return $success;
	}

	/**
	 * Retrieves the value of a configured constant from a file without loading
	 * all the define statements found in the file. The value of the constant
	 * will be evaluated and cached in memory.
	 *
	 * @param string $key the name / key of the constant to search for.
	 * @param string $file the file to search for the constant in.
	 * @param string $content the file contents (optional).
	 * @return mixed|NULL the value of the constant or null of the constant was not found.
	 */
	protected function getConfiguredDefine($key, $file, $content = null) {
		// If we have already retrieved this key, simply return the answer.
		if(defined('TMP_' . $file . '_' . $key)) {
			return constant('TMP_' . $file . '_' . $key);
		}

		// Validate the file exists (and content is useable)
		if(!file_exists($file)) return null;
		if(!is_string($content) || $content === null) {
			$content = @file_get_contents($file);
			if($content === false) return null;
		}

		if(preg_match('|define\(\s*[\'"]' . $key . '[\'"]\s*,\s*(?!\s*\);)(.+?)\s*\);|', $content, $matches)) {
			// This code is already executing from the file when loaded
			// So using eval to load the same code is no more dangerous
			$define = 'define(\'' . 'TMP_' . $file . '_' . $key . '\',' . $matches[1] . ');';
			eval("$define");
			if(defined('TMP_' . $file . '_' . $key)) {
				return constant('TMP_' . $file . '_' . $key);
			}
		}
		return null;
	}

	/**
	 * Checks the server setting and returns a list of warnings.
	 *
	 * @param string $key the name of the setting's key
	 * @param unknown $value the value of the setting
	 * @param unknown $pathKey the name of the corresponding path's key
	 * @return multitype:string any detected warnings
	 */
	protected function checkConfigureServerSetting($key, $value, $pathKey) {
		$warning = array();

		if(preg_match('|^http://[a-z0-9\-\.]+/.+$|i', $value)) {
			$warning[] = sprintf(
				PLUGIN_CONFIG_REMOVE_TRAILING_SUBDIRECTORY,
				$key
			);
			$warning[] = sprintf(
				PLUGIN_CONFIG_SUBDIRECTORY_INFO,
				$pathKey,
				$key
			);
		}
		else if(substr($value, -1) == '/') {
			$warning[] = sprintf(
				PLUGIN_CONFIG_REMOVE_TRAILING_SLASH,
				$key
			);
		}

		return $warning;
	}

	/**
	 * Checks the path setting and returns a list of warnings.
	 *
	 * @param string $key the name of the setting's key
	 * @param unknown $value the value of the setting
	 * @return multitype:string any detected warnings
	 */
	protected function checkConfigurePathSetting($key, $value) {
		$warning = array();

		if(strlen($value) == 0) {
			$warning[] = sprintf(
				PLUGIN_CONFIG_SUBDIRECTORY_EMPTY,
				$key
			);
		}
		else {
			if(substr($value, 0, 1) != '/') {
				$warning[] = sprintf(
					PLUGIN_CONFIG_ADD_LEADING_SLASH,
					$key
				);
			}
			if(substr($value, -1) != '/') {
				$warning[] = sprintf(
					PLUGIN_CONFIG_ADD_TRAILING_SLASH,
					$key
				);
			}
		}

		return $warning;
	}

	/**
	 * Create a generic plugin file to be used during the installation, upgrade,
	 * or removal of this plugin. If the file already exists it will be overwritten.
	 *
	 * @param int $type the type of file to create. This will be one of the
	 *		static "FILE_TYPE_*" variables defined on this class.
	 * @param boolean $install true to create the install init include file,
	 *      false to create the removal init includes file.
	 * @param string $file the fully qualified filename of the file to create,
	 *      if not specified it will be automatically determined.
	 * @return boolean true if the file was created successfully, false otherwise.
	 */
	protected function createPluginFile($type, $install = true, $file = null) {
		global $messageStack;

		// Determine the filename if not supplied
		if($file === null) {
			$files = $this->getAutoLoaderFiles();
			switch($type) {
				case plugin::$FILE_TYPE_ONETIME:
					$file = $files['onetime'];
					break;
				case plugin::$FILE_TYPE_INIT:
					if($install) $file = $files['install'];
					else $file = $files['remove'];
					break;
				default:
			}
		}

		// Create the file contents dynamically
		$contents = $this->createFileContents(plugin::$FILE_TYPE_INIT, $file, $install);

		// Write to the file or display an error message
		if($file === null || @file_put_contents($file, $contents) === false) {
			$messageStack->add(
				sprintf(
					PLUGIN_INSTALL_ERROR_FILE_WRITE,
					$this->getUniqueName(),
					$files[$type]
				),
				'error'
			);
			return false;
		}
		return true;
	}

	/**
	 * Checks to see if a pattern of text exists in a file using a regular
	 * expression search (preg_match).
	 *
	 * @param string $file the file to check
	 * @param string|array $pattern the pattern to search for. It can be either a
	 * 		string or an array of strings.
	 * @param string|array $replace the string or an array with strings to replace.
	 * @return boolean true if the update succeeds, false otherwise.
	 */
	protected function checkInFile($file, $pattern) {
		global $messageStack;

		$success = true;

		$fileContent = @file_get_contents($file);
		if($fileContent === false) {
			$messageStack->add(
				sprintf(
					PLUGIN_INSTALL_ERROR_FILE_NOT_FOUND,
					$this->getUniqueName(),
					$file
				),
				'warning'
			);
			$success = false;
		}
		else {
			// Convert to array if not an array
			if(!is_array($pattern)) $pattern = array($pattern);
			foreach($pattern as $check) {
				if(preg_match($check, $fileContent) !== 1) {
					$messageStack->add(
						sprintf(
							PLUGIN_INSTALL_ERROR_FILE_CHECK,
							$this->getUniqueName(),
							$file
						),
						'warning'
					);
					$success = false;
				}
			}
		}

		return $success;
	}

	/**
	 * Replaces text in a file using a regular expression search and replace.
	 *
	 * @param string $file the file to alter
	 * @param string|array $pattern the pattern to search for. It can be either a
	 * 		string or an array with strings.
	 * @param string|array $replace the string or an array with strings to replace.
	 * @return boolean true if the update succeeds, false otherwise.
	 */
	protected function replaceInFile($file, $pattern, $replace) {
		global $messageStack;

		$success = true;

		$fileContent = @file_get_contents($file);
		if($fileContent === false) {
			$messageStack->add(
				sprintf(
					PLUGIN_INSTALL_ERROR_FILE_NOT_FOUND,
					$this->getUniqueName(),
					$file
				),
				'warning'
			);
			$success = false;
		}
		else {
			$newContent = preg_replace($pattern, $replace, $fileContent);
			if($newContent != $fileContent) {
				if(@rename($file, $file . '.bak') === false) {
					$messageStack->add(
						sprintf(
							PLUGIN_INSTALL_ERROR_FILE_BACKUP,
							$this->getUniqueName(),
							$file
						),
						'warning'
					);
				}
				if(@file_put_contents($file, $newContent) === false) {
					$messageStack->add(
						sprintf(
							PLUGIN_INSTALL_ERROR_FILE_WRITE,
							$this->getUniqueName(),
							$file
						),
						'warning'
					);
					$success = false;
					@rename($file . '.pre-' . strtolower($this->getUniqueKey()), $file);
				}
			}
		}

		return $success;
	}

	/**
	 * Adds the files to a distribution zip file. Files will be added inside
	 * the specified folder. If a specified file is located inside the Zen Cart
	 * admin, the folder 'your_admin_folder' will be created and the file will
	 * be placed inside 'your_admin_folder'. If the file is located inside the
	 * current Zen Cart template folder (or an override folder) the name of the
	 * current template will be replaced with 'your_template_name'. If the files
	 * are indicated as obsolete, blank files will be added instead of the
	 * specified files.
	 *
	 * @param ZipArchive $zip an archive (already opened for writting).
	 * @param string $folder an optional folder to create inside the archive
	 *		(appends the folder name to the filepaths inside the archive).
	 * @param mixed $files a list of files to add to the archive.
	 * @param bool $obsolete if true add an empty file to the archive, otherwise
	 * 		add the actual file to the archive.
	 * @return boolean
	 */
	protected function addToZipArchive($zip, $folder = '', $files = array(), $obsolete = false) {
		global $messageStack, $template_dir;

		$success = true;
		if(!is_array($files)) $files = array($files);

		if($zip !== null && $zip instanceof ZipArchive) {
			// Remove any trailing slash from the folder name
			$folder = rtrim($folder, '/\\');

			// Start by adding an empty directory (just to be on the safe side)
			if(count($files) > 0 && $folder != '' && $zip->getFromName($folder) === false) $zip->addEmptyDir($folder);

			foreach($files as $item) {
				$local = $folder . '/';

				// Determine if this file is from the catalog or admin
				if(!strncmp($item, DIR_FS_ADMIN, strlen(DIR_FS_ADMIN))) {
					$local .= 'your_admin_folder/';
					$local .= substr($item, strlen(DIR_FS_ADMIN));
				}
				else if(!strncmp($item, DIR_FS_CATALOG, strlen(DIR_FS_CATALOG))) {
					$local .= substr($item, strlen(DIR_FS_CATALOG));

					// If the file is an "override" or "template" folder,
					// adjust the local name of the folder
					$local = preg_replace(
						'~(' . DIR_WS_INCLUDES . '/index_filters/|' .
							DIR_WS_LANGUAGES . '|' .
							DIR_WS_LANGUAGES . '(?:[^/]+/)extra_definitions/|' .
							DIR_WS_LANGUAGES . '(?:[^/]+/)html_includes/|' .
							DIR_WS_LANGUAGES . '(?:[^/]+/)modules/order_total/|' .
							DIR_WS_LANGUAGES . '(?:[^/]+/)modules/payment/|' .
							DIR_WS_LANGUAGES . '(?:[^/]+/)modules/shipping/|' .
							DIR_WS_MODULES . '|' .
							DIR_WS_MODULES . '/sideboxes/|' .
							DIR_WS_TEMPLATES .
						')' . $template_dir . '/~',
						'${1}your_template_name/',
						$local
					);
				}
				else {
					$local .= $item;
				}

				// Add the file to the archive
				if($obsolete) {
					$item = $this->createFileContents(plugin::$FILE_TYPE_BLANK, $local);
					if(!$zip->addFromString($local, $item)) {
						$success = false;
					}
				}
				else if(is_readable($item)) {
					$zip->addFile($item, $local);
				}
				else {
					$success = false;
					$messageStack->add(sprintf(ERROR_PLUGIN_READ_FILE, $item));
				}
			}
		}
		return $success;
	}

	/**
	 * Create generic content for common files used during the installation,
	 * upgrade, or removal of this plugin.
	 *
	 * @param int $type the type of file to create. This will be one of the
	 *		static "FILE_TYPE_*" variables defined on this class.
	 * @param boolean $install true to create the install init include file,
	 *      false to create the removal init includes file.
	 * @param string $file the fully qualified filename of the file to create,
	 *      if not specified it will be automatically determined.
	 * @return boolean true if the file was created successfully, false otherwise.
	 */
	private function createFileContents($type, $file, $install = true) {
		$contents = '';
		switch($type) {
			case plugin::$FILE_TYPE_ONETIME:
				$contents = '<?php' . PHP_EOL . PHP_EOL .
				'if (!defined(\'IS_ADMIN_FLAG\')) { die(\'Illegal Access\'); }' . PHP_EOL . PHP_EOL .
				'$autoLoadConfig[999][] = array(' . PHP_EOL . "\t" .
				'\'autoType\' => \'init_script\',' . PHP_EOL . "\t" .
				'\'loadFile\' => \'init_' . strtolower($this->getUniqueKey()) .
				'_' . ($install ? 'install' : 'remove') . '.php\'' . PHP_EOL .
				');' . PHP_EOL;
				break;
			case plugin::$FILE_TYPE_INIT:
				$contents = '<?php' . PHP_EOL . PHP_EOL .
				'if (!defined(\'IS_ADMIN_FLAG\')) { die(\'Illegal Access\'); }' . PHP_EOL . PHP_EOL .
				'@require_once DIR_FS_ADMIN . DIR_WS_CLASSES . \'' . strtolower($this->getUniqueKey()) . '_plugin.php\';' . PHP_EOL .
				'$plugin = new ' . strtolower($this->getUniqueKey()) . '_plugin();' . PHP_EOL .
				'$plugin->' . ($install ? 'install' : 'remove') . '();' . PHP_EOL;
				break;
			case plugin::$FILE_TYPE_BLANK:
				switch(pathinfo($file, PATHINFO_EXTENSION)) {
					case 'php':
					case 'php4':
					case 'php5':
						$contents .= '<?php' . PHP_EOL . PHP_EOL;
					case 'css':
					case 'js':
					case 'sql':
						$contents .= '/* This file is purposely left blank.' . PHP_EOL .
						' *' . PHP_EOL .
						' * This file existed in previous versions of the module. When installing the' . PHP_EOL .
						' * current version of this module this file should be removed from the' . PHP_EOL .
						' * installation. When upgrading from a previous version the end user can' . PHP_EOL .
						' * simply replace (overwrite) the current file with this "blank" file.' . PHP_EOL .
						' * If uploaded, the install / upgrade script removes this file automatially.' . PHP_EOL .
						' *' . PHP_EOL .
						' * This file is included in the distribution to make upgrades easier during' . PHP_EOL .
						' * the process of copying files from the distribution to a Zen Cart' . PHP_EOL .
						' * installation.' . PHP_EOL .
						' */';
						break;
					default:
				}
				break;
			default:
		}

		return $contents;
	}

	/**
	 * Retrieve a list of core plugin files required to install a plugin.
	 * These resources are only needed if they are not already installed.
	 *
	 * @return multitype:string
	 */
	private function getPluginCoreFiles() {
		$retval = array(
			DIR_FS_ADMIN . DIR_WS_CLASSES . 'plugin.php',
			DIR_FS_ADMIN . DIR_WS_CLASSES . strtolower($this->getUniqueKey()) . '_plugin.php'
		);
		foreach(new DirectoryIterator(DIR_FS_ADMIN . DIR_WS_LANGUAGES) as $folder) {
			if($folder->isDot() || !$folder->isDir()) continue;
			$core = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . $folder->getFilename() . '/modules/plugin', '/plugin.php', 'false');
			$subclass = zen_get_file_directory(DIR_FS_ADMIN . DIR_WS_LANGUAGES . $folder->getFilename() . '/modules/plugin', '/' . strtolower($this->getUniqueKey()) . '.php', 'false');
			if(file_exists($core)) $retval[] = $core;
			if(file_exists($subclass)) $retval[] = $subclass;
		}
		return $retval;
	}
}