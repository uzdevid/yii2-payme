<?php

namespace uzdevid\payme\merchant\disposable;

interface DisposableControllerInterface {
    function orderClass(): string;

    function transactionClass(): string;

    function transactionCreated($order, $transaction): void;

    function transactionPerformed($order, $transaction): void;

    function allowRefund($order, $transaction): bool;

    function refund($order, $transaction): void;
}