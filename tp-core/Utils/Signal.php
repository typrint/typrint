<?php

declare(strict_types=1);
// @phpcsf-ignore

/*
 * This file is part of TyPrint.
 *
 * (c) TyPrint Core Team <https://typrint.org>
 *
 * This source file is subject to the GNU General Public License version 3
 * that is with this source code in the file LICENSE.
 */

namespace TP\Utils;

use Swow\Signal as SwowSignal;

/**
 * Class Signal.
 */
class Signal
{
    /**
     * This constant holds SIGHUP value, it's platform-dependent.
     *
     * At macOS platform, this constant means "hangup"
     * At Windows platform, this constant may not exist
     */
    public const int HUP = SwowSignal::HUP;
    /**
     * This constant holds SIGINT value, it's platform-dependent.
     *
     * At macOS and Windows platforms, this constant means "interrupt"
     */
    public const int INT = SwowSignal::INT;
    /**
     * This constant holds SIGQUIT value, it's platform-dependent.
     *
     * At macOS platform, this constant means "quit"
     * At Windows platform, this constant may not exist
     */
    public const int QUIT = SwowSignal::QUIT;
    /**
     * This constant holds SIGILL value, it's platform-dependent.
     *
     * At macOS platform, this constant means "illegal instruction (not reset when caught)"
     * At Windows platform, this constant means "illegal instruction - invalid function image"
     */
    public const int ILL = SwowSignal::ILL;
    /**
     * This constant holds SIGTRAP value, it's platform-dependent.
     *
     * At macOS platform, this constant means "trace trap (not reset when caught)"
     * At Windows platform, this constant may not exist
     */
    public const int TRAP = SwowSignal::TRAP;
    /**
     * This constant holds SIGABRT value, it's platform-dependent.
     *
     * At macOS platform, this constant means "abort()"
     * At Windows platform, this constant may have a value `22` means "abnormal termination triggered by abort call"
     */
    public const int ABRT = SwowSignal::ABRT;
    /**
     * This constant holds SIGIOT value, it's platform-dependent.
     *
     * At macOS and Windows platforms, this constant may not exist
     */
    public const int IOT = SwowSignal::IOT;
    /**
     * This constant holds SIGBUS value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `10`
     * At macOS platform, this constant may have a value `10` means "bus error"
     * At Windows platform, this constant may not exist
     */
    public const int BUS = SwowSignal::BUS;
    /**
     * This constant holds SIGEMT value, it's platform-dependent.
     *
     * At macOS platform, this constant means "EMT instruction"
     * At Windows, Linux x86_64, Linux arm64 and Linux riscv64 platforms, this constant may not exist
     */
    public const int EMT = SwowSignal::EMT;
    /**
     * This constant holds SIGPOLL value, it's platform-dependent.
     *
     * At macOS platform, this constant means "pollable event ([XSR] generated, not supported)"
     * At Linux and Windows platforms, this constant may not exist
     */
    public const int POLL = SwowSignal::POLL;
    /**
     * This constant holds SIGFPE value, it's platform-dependent.
     *
     * At macOS and Windows platforms, this constant means "floating point exception"
     */
    public const int FPE = SwowSignal::FPE;
    /**
     * This constant holds SIGKILL value, it's platform-dependent.
     *
     * At macOS platform, this constant means "kill (cannot be caught or ignored)"
     * At Windows platform, this constant may not exist
     */
    public const int KILL = SwowSignal::KILL;
    /**
     * This constant holds SIGUSR1 value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `16`
     * At macOS platform, this constant may have a value `30` means "user defined signal 1"
     * At Windows platform, this constant may not exist
     */
    public const int USR1 = SwowSignal::USR1;
    /**
     * This constant holds SIGSEGV value, it's platform-dependent.
     *
     * At macOS platform, this constant means "segmentation violation"
     * At Windows platform, this constant means "segment violation"
     */
    public const int SEGV = SwowSignal::SEGV;
    /**
     * This constant holds SIGUSR2 value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `17`
     * At macOS platform, this constant may have a value `31` means "user defined signal 2"
     * At Windows platform, this constant may not exist
     */
    public const int USR2 = SwowSignal::USR2;
    /**
     * This constant holds SIGPIPE value, it's platform-dependent.
     *
     * At macOS platform, this constant means "write on a pipe with no one to read it"
     * At Windows platform, this constant may not exist
     */
    public const int PIPE = SwowSignal::PIPE;
    /**
     * This constant holds SIGALRM value, it's platform-dependent.
     *
     * At macOS platform, this constant means "alarm clock"
     * At Windows platform, this constant may not exist
     */
    public const int ALRM = SwowSignal::ALRM;
    /**
     * This constant holds SIGTERM value, it's platform-dependent.
     *
     * At macOS platform, this constant means "software termination signal from kill"
     * At Windows platform, this constant means "Software termination signal from kill"
     */
    public const int TERM = SwowSignal::TERM;
    /**
     * This constant holds SIGSTKFLT value, it's platform-dependent.
     *
     * At macOS and Windows platforms, this constant may not exist
     */
    public const int STKFLT = SwowSignal::STKFLT;
    /**
     * This constant holds SIGCHLD value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `18`
     * At macOS platform, this constant may have a value `20` means "to parent on child stop or exit"
     * At Windows platform, this constant may not exist
     */
    public const int CHLD = SwowSignal::CHLD;
    /**
     * This constant holds SIGCONT value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `25`
     * At macOS platform, this constant may have a value `19` means "continue a stopped process"
     * At Windows platform, this constant may not exist
     */
    public const int CONT = SwowSignal::CONT;
    /**
     * This constant holds SIGSTOP value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `23`
     * At macOS platform, this constant may have a value `17` means "sendable stop signal not from tty"
     * At Windows platform, this constant may not exist
     */
    public const int STOP = SwowSignal::STOP;
    /**
     * This constant holds SIGTSTP value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `24`
     * At macOS platform, this constant may have a value `18` means "stop signal from tty"
     * At Windows platform, this constant may not exist
     */
    public const int TSTP = SwowSignal::TSTP;
    /**
     * This constant holds SIGBREAK value, it's platform-dependent.
     *
     * At Windows platform, this constant means "Ctrl-Break sequence"
     * At Linux and macOS platforms, this constant may not exist
     */
    public const int BREAK = SwowSignal::BREAK;
    /**
     * This constant holds SIGTTIN value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `26`
     * At macOS platform, this constant means "to readers pgrp upon background tty read"
     * At Windows platform, this constant may not exist
     */
    public const int TTIN = SwowSignal::TTIN;
    /**
     * This constant holds SIGTTOU value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `27`
     * At macOS platform, this constant means "like TTIN for output if (tp->t_local&LTOSTOP)"
     * At Windows platform, this constant may not exist
     */
    public const int TTOU = SwowSignal::TTOU;
    /**
     * This constant holds SIGURG value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `21`
     * At macOS platform, this constant may have a value `16` means "urgent condition on IO channel"
     * At Windows platform, this constant may not exist
     */
    public const int URG = SwowSignal::URG;
    /**
     * This constant holds SIGXCPU value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `30`
     * At macOS platform, this constant means "exceeded CPU time limit"
     * At Windows platform, this constant may not exist
     */
    public const int XCPU = SwowSignal::XCPU;
    /**
     * This constant holds SIGXFSZ value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `31`
     * At macOS platform, this constant means "exceeded file size limit"
     * At Windows platform, this constant may not exist
     */
    public const int XFSZ = SwowSignal::XFSZ;
    /**
     * This constant holds SIGVTALRM value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `28`
     * At macOS platform, this constant means "virtual time alarm"
     * At Windows platform, this constant may not exist
     */
    public const int VTALRM = SwowSignal::VTALRM;
    /**
     * This constant holds SIGPROF value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `29`
     * At macOS platform, this constant means "profiling time alarm"
     * At Windows platform, this constant may not exist
     */
    public const int PROF = SwowSignal::PROF;
    /**
     * This constant holds SIGWINCH value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `20`
     * At macOS platform, this constant means "window size changes"
     * At Windows platform, this constant may not exist
     */
    public const int WINCH = SwowSignal::WINCH;
    /**
     * This constant holds SIGINFO value, it's platform-dependent.
     *
     * At macOS platform, this constant means "information request"
     * At Linux and Windows platforms, this constant may not exist
     */
    public const int INFO = SwowSignal::INFO;
    /**
     * This constant holds SIGIO value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `22`
     * At macOS platform, this constant may have a value `23` means "input/output possible signal"
     * At Windows platform, this constant may not exist
     */
    public const int IO = SwowSignal::IO;
    /**
     * This constant holds SIGLOST value, it's platform-dependent.
     *
     * At macOS and Windows platforms, this constant may not exist
     */
    public const int LOST = SwowSignal::LOST;
    /**
     * This constant holds SIGPWR value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `19`
     * At macOS and Windows platforms, this constant may not exist
     */
    public const int PWR = SwowSignal::PWR;
    /**
     * This constant holds SIGSYS value, it's platform-dependent.
     *
     * At Linux mips64 platform, this constant may have a value `12`
     * At macOS platform, this constant may have a value `12` means "bad argument to system call"
     * At Windows platform, this constant may not exist
     */
    public const int SYS = SwowSignal::SYS;

    public static function wait(int $signal, int $timeout = -1): void
    {
        SwowSignal::wait($signal, $timeout);
    }

    public static function kill(int $pid, int $signal): void
    {
        SwowSignal::kill($pid, $signal);
    }
}
