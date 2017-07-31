--
-- Structure de la table `algorithm`
--

CREATE TABLE IF NOT EXISTS `algorithm` (
`id` int(11) NOT NULL,
  `name` varchar(63) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8;

--
-- Contenu de la table `algorithm`
--

INSERT INTO `algorithm` (`id`, `name`) VALUES
(1, 'hmac-md5'),
(2, 'hmac-sha1'),
(3, 'hmac-sha224'),
(4, 'hmac-sha256'),
(5, 'hmac-sha384'),
(6, 'hmac-sha512');

-- --------------------------------------------------------

--
-- Structure de la table `host`
--

CREATE TABLE IF NOT EXISTS `host` (
`id` int(11) NOT NULL,
  `name` varchar(63) NOT NULL,
  `ip` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `signkeys`
--

CREATE TABLE IF NOT EXISTS `signkeys` (
`id` int(11) NOT NULL,
  `host_id` int(11) NOT NULL,
  `name` varchar(63) NOT NULL,
  `algorithm_id` int(11) NOT NULL,
  `secret` varchar(255) DEFAULT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE IF NOT EXISTS `users` (
`id` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `password` varchar(255) DEFAULT NULL,
  `name` varchar(64) DEFAULT NULL,
  `theme` varchar(32) DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `is_admin` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;

--
-- Contenu de la table `users`
--

INSERT INTO `users` (`id`, `login`, `password`, `name`, `theme`, `email`, `is_admin`) VALUES
(1, 'admin', '$1$WFeOvvwi$4cnulMzHC1wj.XemP0uph1', 'Admin', NULL, 'admin@localhost', 1);

-- --------------------------------------------------------

--
-- Structure de la table `users_zones`
--

CREATE TABLE IF NOT EXISTS `users_zones` (
  `users_id` int(11) NOT NULL,
  `zones_id` int(11) NOT NULL,
  `rights` enum('read','write') NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

-- --------------------------------------------------------

--
-- Structure de la table `zones`
--

CREATE TABLE IF NOT EXISTS `zones` (
`id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `signkeys_id` int(11) NOT NULL
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;

--
-- Index pour les tables exportées
--

--
-- Index pour la table `algorithm`
--
ALTER TABLE `algorithm`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `host`
--
ALTER TABLE `host`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`);

--
-- Index pour la table `signkeys`
--
ALTER TABLE `signkeys`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `host` (`host_id`,`name`), ADD KEY `algorithm` (`algorithm_id`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `login` (`login`), ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `users_zones`
--
ALTER TABLE `users_zones`
 ADD PRIMARY KEY (`users_id`,`zones_id`,`rights`), ADD KEY `zones_id` (`zones_id`);

--
-- Index pour la table `zones`
--
ALTER TABLE `zones`
 ADD PRIMARY KEY (`id`), ADD UNIQUE KEY `name` (`name`), ADD KEY `key` (`signkeys_id`);

--
-- AUTO_INCREMENT pour les tables exportées
--

--
-- AUTO_INCREMENT pour la table `algorithm`
--
ALTER TABLE `algorithm`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=7;
--
-- AUTO_INCREMENT pour la table `host`
--
ALTER TABLE `host`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pour la table `signkeys`
--
ALTER TABLE `signkeys`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=2;
--
-- AUTO_INCREMENT pour la table `zones`
--
ALTER TABLE `zones`
MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=1;
--
-- Contraintes pour les tables exportées
--

--
-- Contraintes pour la table `signkeys`
--
ALTER TABLE `signkeys`
ADD CONSTRAINT `signkeys_ibfk_1` FOREIGN KEY (`host_id`) REFERENCES `host` (`id`),
ADD CONSTRAINT `signkeys_ibfk_2` FOREIGN KEY (`algorithm_id`) REFERENCES `algorithm` (`id`);

--
-- Contraintes pour la table `users_zones`
--
ALTER TABLE `users_zones`
ADD CONSTRAINT `users_zones_ibfk_1` FOREIGN KEY (`users_id`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
ADD CONSTRAINT `users_zones_ibfk_2` FOREIGN KEY (`zones_id`) REFERENCES `zones` (`id`);

--
-- Contraintes pour la table `zones`
--
ALTER TABLE `zones`
ADD CONSTRAINT `zones_ibfk_1` FOREIGN KEY (`signkeys_id`) REFERENCES `signkeys` (`id`);
