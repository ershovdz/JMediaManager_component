DROP TABLE IF EXISTS `#__jmm_plugin`;

CREATE TABLE IF NOT EXISTS `#__jmm_plugin` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(256) NOT NULL,
  `author` varchar(256) NOT NULL,
  `authoremail` varchar(256) NOT NULL,
  `version` varchar(256) NOT NULL,
  `status` int(1) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

INSERT INTO `#__jmm_plugin` (`id`, `name`, `author`, `authoremail`, `version`, `status`) VALUES
('', 'DatsoGallery_Plugin', 'Alexandr Ershov', 'support@jmediamanager.com', '1.2', 0),
('', 'JoomGallery_Plugin', 'Alexandr Ershov', 'support@jmediamanager.com', '1.2', 0);
