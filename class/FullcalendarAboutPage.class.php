<?php
/* Copyright (C) 2026
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

namespace fullcalendar;

/**
 * Render the module about page without external network calls.
 */
class FullcalendarAboutPage
{
	const MODULE_NAME = 'fullcalendar';
	const DOCUMENTATION_URL = '';
	const SUPPORT_URL = '';

	/**
	 * Render the about page.
	 *
	 * @param \DolibarrModules $moduleDescriptor Module descriptor
	 * @return string
	 */
	public function render(\DolibarrModules $moduleDescriptor): string
	{
		$moduleName = !empty($moduleDescriptor->name) ? $moduleDescriptor->name : self::MODULE_NAME;
		$version = !empty($moduleDescriptor->version) ? $moduleDescriptor->version : '';
		$dolibarrMin = '';
		if (!empty($moduleDescriptor->need_dolibarr_version) && is_array($moduleDescriptor->need_dolibarr_version)) {
			$dolibarrMin = implode('.', $moduleDescriptor->need_dolibarr_version);
		}

		$content = '<div class="fichecenter">';
		$content .= '<div class="underbanner clearboth"></div>';
		$content .= '<table class="border centpercent tableforfield">';
		$content .= '<tr><td class="titlefield">'.$this->translateHtml('Module').'</td><td>'.dol_escape_htmltag($moduleName).'</td></tr>';
		$content .= '<tr><td>'.$this->translateHtml('Version').'</td><td>'.dol_escape_htmltag($version).'</td></tr>';
		if ($dolibarrMin !== '') {
			$content .= '<tr><td>'.$this->translateHtml('FullcalendarAboutDolibarrMinVersion').'</td><td>'.dol_escape_htmltag($dolibarrMin).'</td></tr>';
		}
		$content .= '<tr><td>'.$this->translateHtml('License').'</td><td>'.$this->translateHtml('FullcalendarAboutLicense').'</td></tr>';
		$content .= '<tr><td>'.$this->translateHtml('FullcalendarAboutDependencies').'</td><td>'.$this->translateHtml('FullcalendarAboutNoExternalDependency').'</td></tr>';
		if (self::DOCUMENTATION_URL !== '') {
			$content .= '<tr><td>'.$this->translateHtml('Documentation').'</td><td><a href="'.dol_escape_htmltag(self::DOCUMENTATION_URL).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag(self::DOCUMENTATION_URL).'</a></td></tr>';
		}
		if (self::SUPPORT_URL !== '') {
			$content .= '<tr><td>'.$this->translateHtml('Support').'</td><td><a href="'.dol_escape_htmltag(self::SUPPORT_URL).'" target="_blank" rel="noopener noreferrer">'.dol_escape_htmltag(self::SUPPORT_URL).'</a></td></tr>';
		}
		$content .= '</table>';
		$content .= '</div>';

		return $content;
	}

	/**
	 * Return an escaped translated label for HTML output.
	 *
	 * @param string $translationKey Translation key
	 * @return string
	 */
	private function translateHtml(string $translationKey): string
	{
		global $langs;

		return dol_escape_htmltag($langs->trans($translationKey));
	}
}
