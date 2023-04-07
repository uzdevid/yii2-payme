<?php

namespace uzdevid\payme\merchant\savings;

interface SavingsControllerInterface {
    function userClass(): string;

    function transactionClass(): string;

    function checkAmount(int $amount): bool;

    function allowTransaction(array $payload): bool;

    function transactionCreated($transaction): void;

    function allowRefund($transaction): bool;

    function userBalance(int $userId): int;

    function refund($transaction): void;
}