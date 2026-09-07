<?php

/*
 * Point d'entree pour une plateforme sans systeme de fichiers persistant.
 *
 * Vercel sert un fichier PHP par fonction. Ce fichier ne sert QUE la : un
 * hebergement ordinaire passe par public/index.php, qui n'a besoin d'aucun
 * contournement.
 *
 * Voir preparer-environnement.php pour ce qu'il faut deplacer, et pourquoi.
 */

require __DIR__.'/preparer-environnement.php';

require __DIR__.'/../public/index.php';
