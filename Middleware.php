<?php

namespace Pure\Routing;

abstract class Middleware
{
	// ritorna true per continuare la navigazione
	// false per fermare l'esecuzione
    public abstract function handle();
}

?>
