<?php

namespace uzdevid\payme\merchant\disposable;

interface PaymeControllerInterface {
    function orderClass(): string;

    function transactionClass(): string;
}