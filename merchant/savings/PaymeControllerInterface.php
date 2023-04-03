<?php

namespace uzdevid\payme\merchant\savings;

interface PaymeControllerInterface {
    function userClass(): string;

    function transactionClass(): string;

    function userBalance(int $userId): int;

    function checkAmount(int $amount): bool;

    function allowTransaction(array $payload): bool;

    function transactionCreated($transaction): void;

    function allowRefund($transaction): bool;

    function refund($transaction): void;
}