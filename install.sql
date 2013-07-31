CREATE TABLE `dbv_revisions` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `commit` varchar(255) DEFAULT NULL,
  `revision` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;