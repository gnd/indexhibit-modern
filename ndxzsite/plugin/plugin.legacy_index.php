<?php if (!defined('SITE')) exit('No direct script access allowed');

/**
* Index (menu) in the markup of Indexhibit 0.7x
*
* Used by the themes that were imported from an old ndxz-studio site (see
* 'Upgrading from Indexhibit 0.7x' in the README), so that their style.css
* keeps working: one flat <ul> per section with an optional
* <li class='section-title'>, the current page is <li class='active'>.
*
* Use it in a theme with <plugin:legacy_index:load_index />
*/
#[AllowDynamicProperties]
class Legacy_index
{
	function load_index()
	{
		$OBJ =& get_instance();

		// obj_org = 1 was the 'chronological' index of 0.7x, everything else 'sectional'
		$chrono = (isset($OBJ->vars->exhibit['obj_org']) && ($OBJ->vars->exhibit['obj_org'] == 1));
		$current = (isset($OBJ->vars->exhibit['id'])) ? $OBJ->vars->exhibit['id'] : 0;

		// the first page (home = 1) is a normal link in the old index, the
		// section pages and the tag page that are new in 2.x are not shown
		$pages = $OBJ->db->fetchArray("SELECT id, title, url, link, target,
			sec_desc, sec_disp, year, secid, sec_proj
			FROM ".PX."objects, ".PX."sections
			WHERE status = '1'
			AND hidden != '1'
			AND sec_hide != '1'
			AND section_id = secid
			AND object = 'exhibits'
			AND (section_top = '0' OR home = '1')
			ORDER BY sec_ord ASC, " . (($chrono) ? "year DESC, " : '') . "ord ASC");

		if (!$pages) return '';

		$order = array();

		foreach ($pages as $page)
		{
			// the projects (sec_proj = 1) are grouped by year when chronological
			$key = ($chrono && $page['sec_proj'] == 1) ? $page['year'] : $page['sec_desc'];
			$order[$key][] = $page;
		}

		$s = '';

		foreach ($order as $key => $group)
		{
			$s .= "<ul>\n";

			if ($group[0]['sec_disp'] == 1) $s .= "<li class='section-title'>" . ndxz_esc($key) . "</li>\n";

			foreach ($group as $page)
			{
				$active = ($current == $page['id']) ? " class='active'" : '';

				if ($page['link'] != '')
				{
					$href = ndxz_esc($page['link']);
					$target = ($page['target'] == 1) ? " target='_blank'" : '';
				}
				else
				{
					$href = $OBJ->baseurl . ndxz_rewriter($page['url']);
					$target = '';
				}

				$s .= "<li$active><a href='" . $href . "'$target onclick=\"do_click();\">" . ndxz_esc($page['title']) . "</a></li>\n";
			}

			$s .= "</ul>\n\n";
		}

		return $s;
	}
}
