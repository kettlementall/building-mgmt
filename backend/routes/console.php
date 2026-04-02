<?php

use Illuminate\Support\Facades\Schedule;

// 每月1日 00:05 自動產帳單並寄送通知
Schedule::command('bills:generate --notify')->monthlyOn(1, '00:05');

// 每天早上 09:00 標記逾期帳單並發催繳通知
Schedule::command('bills:mark-overdue --notify')->dailyAt('09:00');
