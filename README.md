### Zadání
Napiště "univerzální" program, který přečte libovolně dlouhý textový soubor.
Řádek po řádku bude aplikovat uživatelské filtry a dekorátory. Výstupem programu
bude počet stejných (upravených) řádků a jejich četností.

Použijte co nejvíce vlastností moderního PHP. Minimálně:
- [Iterables](http://php.net/manual/en/language.types.iterable.php) or [Generators](https://www.php.net/manual/en/language.generators.php)
- [Anonymous functions](http://php.net/manual/en/functions.anonymous.php), especially [Callables](http://php.net/manual/en/language.types.callable.php)
- [Types](http://php.net/manual/en/migration70.new-features.php#migration70.new-features.scalar-type-declarations)
- ... a volitelně [další](http://php.net/manual/en/langref.php)

#### Bonus
Upravte program tak, aby vypisoval průběžný stav nekonečného streamu.

### Příklad
```bash
php old.php example.log
```

#### Vstupní soubor
```
[2018-03-13 12:16:10] test.DEBUG: Test message [] []
[2018-03-13 12:16:10] test.ERROR: Test message [] []
[2018-03-13 12:16:10] test.WARNING: Test message [] []
[2018-03-13 12:16:10] test.WARNING: Test message [] []
[2018-03-13 12:16:10] test.INFO: Test message [] []
[2018-03-13 12:16:10] test.NOTICE: Test message [] []
[2018-03-13 12:16:10] test.EMERGENCY: Test message [] []
[2018-03-13 12:16:10] test.ALERT: Test message [] []
[2018-03-13 12:16:10] test.ERROR: Test message [] []
[2018-03-13 12:16:10] test.NOTICE: Test message [] []
```

#### Výstup
```
error: 2
warning: 2
notice: 2
info: 1
emergency: 1
alert: 1
```

#### Implementace ve "starém" PHP
```php
<?php
$pattern = '/test\.(\w+)/';

// read and parse file
$log  = file_get_contents($argv[1]);
$rows = explode(PHP_EOL, $log);

// build stats
$stats = array();
foreach ($rows as $row) {
    // decorator: extract log level
    if (preg_match($pattern, $row, $matches)) {
        $level = strtolower($matches[1]);

        // filter: do not accept DEBUG
        if ($level != 'debug') {
            if (array_key_exists($level, $stats)) {
                $stats[$level]++;
            } else {
                $stats[$level] = 1;
            }
        }
    }
}

// show stats
arsort($stats);
foreach ($stats as $level => $count) {
    echo "$level: $count" . PHP_EOL;
}
```

---

### Where to start
Literature and other sources for PHP developers
- in English
 - https://secure.php.net/ (for anybody, get off-line here: https://devdocs.io/php/)
 - https://www.w3schools.com/php/default.asp (good for beginners)
 - https://php7explained.com/ (best source of informations about PHP 7.x)
 - https://martinfowler.com/ (not about PHP but good reading for any developer)
 - https://github.com/paragonie/awesome-appsec (it's not only about PHP but also about applicaton security)
 - https://github.com/ziadoz/awesome-php
- in Czech
 - https://php.vrana.cz/kniha-1001-tipu-a-triku-pro-php.php
 - https://books.google.cz/books/about/N%C3%A1vrhov%C3%A9_vzory_v_PHP.html?id=eBrqCwAAQBAJ&redir_esc=y

Feel free to add yours favorite, thanks.

### Tools
- https://github.com/phpstan/phpstan (can spot a "bugs" in modern PHP code)
- https://github.com/FriendsOfPHP/PHP-CS-Fixer

### Řešení

Řešení je spustitelné následujícím způsobem:

```sh
php solution.php INPUT [stream]
```

Prvním parametrem je cesta ke vstupnímu souboru. *Čtení ze standardního vstupu není implementováno.*
Druhým, volitelným parametrem je řetězec `stream`, který umožní práci s potenciálně nekonečným vstupem.
Bez parametru `stream` program přečte celý vstupní soubor a vrátí komplení výsledný výstup.
S parametrem `stream` program očekává přidání nových řádků ke vstupnímu souboru
a při každé takové skutečnosti vypíše nový akumulovaný výsledný výstup.
*Funguje tedy na způsob `tail --follow`.*

Soubor `solution.php` využívá obecnou implementaci z adresáře `solution/`
a demonstruje příklady možných konfigurací třídy `\Solution\Processor`
a využití pomocných utilit z třídy `\Solution\Util`.
*Nakonec vypíše výsledek stejným způsobem, jak tomu bylo v příkladu `old.php`,
tedu tuto část jsem nijak nemodernizoval.*

Konfigurace třídy `Processor` je modelována dvěma funkcemi `$decorator` a `$filter`.
Případ žádného dokorátoru resp. filtru se modeluje identitou resp. konstantní funkcí vracející `true`.
Případ několika dekorátorů se modeluje skládáním funkcí.
Případ několika filtrů se modeluje konjunkcí nebo disjunkcí daných predikátů na vstupech.
Jako výchozí je zvolena varianta konjunkce filtrů.
Konstruktor umožňuje jen vynechání dekorátoru nebo filteru,
ale pro komplexnější interface je určena statická medoda `make`,
která navíc příjme pole dekorátorů nebo filtrů. Na filterech provede konjunkci.
*Metoda `make` je však omezena na `Closure` (nebere obecné `callable`)
kvůli nejednoznačnosti callable polí.*
Pro manuální skládání dekorátorů a filterů jsou k dispozici statické metody
`Util::seq`, `Util::and` a `Util::or`.

Pro spuštění výpočtu na daném vstupu jsou metody `process` a `processStream`.
Obě příjmou iterable řetězců (každý řetězec reprezentuje řádek vstupu).
Zpracování vstupu na iterable objekt zařídí volající.
Lze k tomu použít pomocnou statickou metodu `Util::read`, která
je schopna zpracovat celý soubor po řádcích a případně čekat na přidání nových řádků.
Metoda `process` zpracuje konečný počet řádků a vrátí jeden výsledek,
zatímco metoda `processStream` vrací generátor,
který postupně vydává výsledky po zpracování každého řádku.
Výsledek je reprezentován asociativním polem,
kde klíče odpovídají řádkům a hodnoty jejich četnosti.

Využívám standardní Composer autoloader nastavený v `composer.json`.
V kódu využívám některých konstruktů z php 8.1 (např. syntaxi `f(...)`).
S PHPStanem jsem zatím nepracoval, ale místo něj
používám statický analyzátor Psalm (konfigurovaný v `psalm.xml`)
a některé dock-block anotace odpovídají právě jeho specifickým typům
(např. `array<string, int>`, `callable (string): string`).
