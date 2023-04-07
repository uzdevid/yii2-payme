<?php

namespace uzdevid\payme\merchant\disposable;

interface DisposableControllerInterface {
    function orderClass(): string;

    function transactionClass(): string;
}