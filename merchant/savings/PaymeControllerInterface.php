<?php

namespace uzdevid\payme\merchant\savings;

interface PaymeControllerInterface {
    function userClass(): string;

    function transactionClass(): string;

    function userBalance(int $userId): int;

    function checkAmount(int $amount): bool;
}