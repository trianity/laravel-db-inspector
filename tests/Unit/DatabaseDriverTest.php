<?php

declare(strict_types=1);
use Trianity\LaravelDbInspector\Database\DatabaseDriver;

it('treats mysql as mysql compatible', function (): void {
    $driver = new DatabaseDriver;

    expect($driver::isMySqlCompatible('mysql'))->toBeTrue();
});

it('treats mariadb as mysql compatible', function (): void {
    $driver = new DatabaseDriver;

    expect($driver::isMySqlCompatible('mariadb'))->toBeTrue();
});

it('does not treat pgsql as mysql compatible', function (): void {
    $driver = new DatabaseDriver;

    expect($driver::isMySqlCompatible('pgsql'))->toBeFalse();
});

it('identifies pgsql as postgresql', function (): void {
    $driver = new DatabaseDriver;

    expect($driver::isPostgreSql('pgsql'))->toBeTrue();
});
