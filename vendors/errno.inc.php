<?php
define('EISDIR', 21);
define('EPROTONOSUPPORT', 93);
define('ETXTBSY', 26);
define('ETOOMANYREFS', 109);
define('ETIME', 62);
define('ESOCKTNOSUPPORT', 94);
define('ENOTTY', 25);
define('ENAMETOOLONG', 36);
define('ENETRESET', 102);
define('EAGAIN', 11);
define('EMSGSIZE', 90);
define('ECONNRESET', 104);
define('EL3HLT', 46);
define('ELIBEXEC', 83);
define('EMLINK', 31);
define('ESTRPIPE', 86);
define('EUCLEAN', 117);
define('ENOTSOCK', 88);
define('ESHUTDOWN', 108);
define('ENOLCK', 37);
define('EDEADLK', 35);
define('EFBIG', 27);
define('EBADRQC', 56);
define('ECANCELED', 125);
define('EEXIST', 17);
define('ENOLINK', 67);
define('EPROTO', 71);
define('EL3RST', 47);
define('EREMOTEIO', 121);
define('ESRMNT', 69);
define('EKEYEXPIRED', 127);
define('EIDRM', 43);
define('EADDRNOTAVAIL', 99);
define('EPERM', 1);
define('ENOTUNIQ', 76);
define('ELNRNG', 48);
define('ENOSPC', 28);
define('EPROTOTYPE', 91);
define('EUNATCH', 49);
define('EISCONN', 106);
define('ELIBBAD', 80);
define('ERANGE', 34);
define('ESTALE', 116);
define('ENOPROTOOPT', 92);
define('ELOOP', 40);
define('ECHILD', 10);
define('EREMOTE', 66);
define('ENOTRECOVERABLE', 131);
define('ENOBUFS', 105);
define('EDESTADDRREQ', 89);
define('EINTR', 4);
define('EADV', 68);
define('ETIMEDOUT', 110);
define('ENOSYS', 38);
define('ENOMEDIUM', 123);
define('EUSERS', 87);
define('EALREADY', 114);
define('ELIBMAX', 82);
define('E2BIG', 7);
define('ENXIO', 6);
define('EPIPE', 32);
define('EMFILE', 24);
define('ECONNREFUSED', 111);
define('EHOSTDOWN', 112);
define('EBFONT', 59);
define('EXFULL', 54);
define('EOPNOTSUPP', 95);
define('EBUSY', 16);
define('EINPROGRESS', 115);
define('ENFILE', 23);
define('EREMCHG', 78);
define('EADDRINUSE', 98);
define('ENOMEM', 12);
define('ENOSR', 63);
define('ECONNABORTED', 103);
define('EISNAM', 120);
define('EOWNERDEAD', 130);
define('ELIBSCN', 81);
define('ENOTCONN', 107);
define('EFAULT', 14);
define('ELIBACC', 79);
define('ENETUNREACH', 101);
define('EBADSLT', 57);
define('EKEYREJECTED', 129);
define('EDQUOT', 122);
define('EMEDIUMTYPE', 124);
define('ENOTNAM', 118);
define('ENOENT', 2);
define('EMULTIHOP', 72);
define('ESPIPE', 29);
define('ECOMM', 70);
define('EBADMSG', 74);
define('EROFS', 30);
define('ENOPKG', 65);
define('ENOTEMPTY', 39);
define('EDOM', 33);
define('ENOSTR', 60);
define('ENOTDIR', 20);
define('EILSEQ', 84);
define('EACCES', 13);
define('EL2NSYNC', 45);
define('ENETDOWN', 100);
define('ENOEXEC', 8);
define('EPFNOSUPPORT', 96);
define('ERESTART', 85);
define('EIO', 5);
define('EBADF', 9);
define('EBADE', 52);
define('ENONET', 64);
define('ECHRNG', 44);
define('ENOKEY', 126);
define('EDOTDOT', 73);
define('EBADFD', 77);
define('EBADR', 53);
define('EAFNOSUPPORT', 97);
define('ESRCH', 3);
define('EHOSTUNREACH', 113);
define('EXDEV', 18);
define('ENAVAIL', 119);
define('EKEYREVOKED', 128);
define('EINVAL', 22);
define('ENOTBLK', 15);
define('ENODATA', 61);
define('EL2HLT', 51);
define('EOVERFLOW', 75);
define('ENOCSI', 50);
define('ENOMSG', 42);
define('ENODEV', 19);
define('ENOANO', 55);

$errno_str_to_int = array(
	'Level 3 reset' => 47,
	'Channel number out of range' => 44,
	'Identifier removed' => 43,
	'Attempting to link in too many shared libraries' => 82,
	'Value too large for defined data type' => 75,
	'No route to host' => 113,
	'Block device required' => 15,
	'No record locks available' => 37,
	'No such device' => 19,
	'No data available' => 61,
	'Is a directory' => 21,
	'Not a directory' => 20,
	'Connection timed out' => 110,
	'Too many users' => 87,
	'File name too long' => 36,
	'Quota exceeded' => 122,
	'No space left on device' => 28,
	'Network is down' => 100,
	'Streams pipe error' => 86,
	'Level 2 not synchronized' => 45,
	'Try again' => 11,
	'Link number out of range' => 48,
	'.lib section in a.out corrupted' => 81,
	'Level 2 halted' => 51,
	'Software caused connection abort' => 103,
	'Cannot send after transport endpoint shutdown' => 108,
	'Stale NFS file handle' => 116,
	'Math result not representable' => 34,
	'Remote address changed' => 78,
	'Structure needs cleaning' => 117,
	'Bad file number' => 9,
	'I/O error' => 5,
	'Protocol family not supported' => 96,
	'Key was rejected by service' => 129,
	'Interrupted system call' => 4,
	'File exists' => 17,
	'Host is down' => 112,
	'Device or resource busy' => 16,
	'Accessing a corrupted shared library' => 80,
	'Wrong medium type' => 124,
	'Read-only file system' => 30,
	'Is a named type file' => 120,
	'Broken pipe' => 32,
	'No such file or directory' => 2,
	'Device not a stream' => 60,
	'No buffer space available' => 105,
	'Machine is not on the network' => 64,
	'Owner died' => 130,
	'File too large' => 27,
	'Illegal byte sequence' => 84,
	'Cannot assign requested address' => 99,
	'Timer expired' => 62,
	'Link has been severed' => 67,
	'Message too long' => 90,
	'Package not installed' => 65,
	'Connection reset by peer' => 104,
	'No message of desired type' => 42,
	'Invalid slot' => 57,
	'Destination address required' => 89,
	'Too many links' => 31,
	'Out of streams resources' => 63,
	'File descriptor in bad state' => 77,
	'Can not access a needed shared library' => 79,
	'Bad font file format' => 59,
	'Protocol not available' => 92,
	'State not recoverable' => 131,
	'Argument list too long' => 7,
	'Invalid exchange' => 52,
	'Protocol not supported' => 93,
	'Socket type not supported' => 94,
	'No child processes' => 10,
	'Level 3 halted' => 46,
	'RFS specific error' => 73,
	'Too many references: cannot splice' => 109,
	'Not a XENIX named type file' => 118,
	'No such process' => 3,
	'No medium found' => 123,
	'Operation Canceled' => 125,
	'Object is remote' => 66,
	'Connection refused' => 111,
	'Too many open files' => 24,
	'Directory not empty' => 39,
	'Cannot exec a shared library directly' => 83,
	'Operation not permitted' => 1,
	'No XENIX semaphores available' => 119,
	'Bad address' => 14,
	'Exchange full' => 54,
	'No CSI structure available' => 50,
	'Cross-device link' => 18,
	'Transport endpoint is already connected' => 106,
	'Socket operation on non-socket' => 88,
	'Multihop attempted' => 72,
	'Name not unique on network' => 76,
	'Protocol driver not attached' => 49,
	'Srmount error' => 69,
	'Invalid request descriptor' => 53,
	'Text file busy' => 26,
	'Network is unreachable' => 101,
	'Key has been revoked' => 128,
	'Network dropped connection because of reset' => 102,
	'File table overflow' => 23,
	'Operation not supported on transport endpoint' => 95,
	'Invalid argument' => 22,
	'Transport endpoint is not connected' => 107,
	'Address family not supported by protocol' => 97,
	'Too many symbolic links encountered' => 40,
	'Invalid request code' => 56,
	'Communication error on send' => 70,
	'Advertise error' => 68,
	'Operation already in progress' => 114,
	'Required key not available' => 126,
	'Resource deadlock would occur' => 35,
	'Not a typewriter' => 25,
	'Math argument out of domain of func' => 33,
	'Permission denied' => 13,
	'Key has expired' => 127,
	'Protocol wrong type for socket' => 91,
	'Operation now in progress' => 115,
	'Exec format error' => 8,
	'Protocol error' => 71,
	'Interrupted system call should be restarted' => 85,
	'No anode' => 55,
	'Out of memory' => 12,
	'Illegal seek' => 29,
	'No such device or address' => 6,
	'Remote I/O error' => 121,
	'Not a data message' => 74,
	'Function not implemented' => 38,
	'Address already in use' => 98,
);

$errno_int_to_str = array(
	131 => 'State not recoverable',
	130 => 'Owner died',
	24 => 'Too many open files',
	25 => 'Not a typewriter',
	26 => 'Text file busy',
	27 => 'File too large',
	20 => 'Not a directory',
	21 => 'Is a directory',
	22 => 'Invalid argument',
	23 => 'File table overflow',
	28 => 'No space left on device',
	29 => 'Illegal seek',
	4 => 'Interrupted system call',
	8 => 'Exec format error',
	119 => 'No XENIX semaphores available',
	120 => 'Is a named type file',
	121 => 'Remote I/O error',
	122 => 'Quota exceeded',
	123 => 'No medium found',
	124 => 'Wrong medium type',
	125 => 'Operation Canceled',
	126 => 'Required key not available',
	127 => 'Key has expired',
	128 => 'Key has been revoked',
	129 => 'Key was rejected by service',
	59 => 'Bad font file format',
	55 => 'No anode',
	54 => 'Exchange full',
	57 => 'Invalid slot',
	56 => 'Invalid request code',
	51 => 'Level 2 halted',
	50 => 'No CSI structure available',
	53 => 'Invalid request descriptor',
	52 => 'Invalid exchange',
	115 => 'Operation now in progress',
	114 => 'Operation already in progress',
	88 => 'Socket operation on non-socket',
	89 => 'Destination address required',
	111 => 'Connection refused',
	110 => 'Connection timed out',
	113 => 'No route to host',
	112 => 'Host is down',
	82 => 'Attempting to link in too many shared libraries',
	83 => 'Cannot exec a shared library directly',
	80 => 'Accessing a corrupted shared library',
	81 => '.lib section in a.out corrupted',
	86 => 'Streams pipe error',
	87 => 'Too many users',
	84 => 'Illegal byte sequence',
	85 => 'Interrupted system call should be restarted',
	3 => 'No such process',
	7 => 'Argument list too long',
	108 => 'Cannot send after transport endpoint shutdown',
	109 => 'Too many references: cannot splice',
	102 => 'Network dropped connection because of reset',
	103 => 'Software caused connection abort',
	100 => 'Network is down',
	101 => 'Network is unreachable',
	106 => 'Transport endpoint is already connected',
	107 => 'Transport endpoint is not connected',
	104 => 'Connection reset by peer',
	105 => 'No buffer space available',
	39 => 'Directory not empty',
	38 => 'Function not implemented',
	33 => 'Math argument out of domain of func',
	32 => 'Broken pipe',
	31 => 'Too many links',
	30 => 'Read-only file system',
	37 => 'No record locks available',
	36 => 'File name too long',
	35 => 'Resource deadlock would occur',
	34 => 'Math result not representable',
	60 => 'Device not a stream',
	61 => 'No data available',
	62 => 'Timer expired',
	63 => 'Out of streams resources',
	64 => 'Machine is not on the network',
	65 => 'Package not installed',
	66 => 'Object is remote',
	67 => 'Link has been severed',
	68 => 'Advertise error',
	69 => 'Srmount error',
	2 => 'No such file or directory',
	6 => 'No such device or address',
	99 => 'Cannot assign requested address',
	98 => 'Address already in use',
	91 => 'Protocol wrong type for socket',
	90 => 'Message too long',
	93 => 'Protocol not supported',
	92 => 'Protocol not available',
	95 => 'Operation not supported on transport endpoint',
	94 => 'Socket type not supported',
	97 => 'Address family not supported by protocol',
	96 => 'Protocol family not supported',
	11 => 'Try again',
	10 => 'No child processes',
	13 => 'Permission denied',
	12 => 'Out of memory',
	15 => 'Block device required',
	14 => 'Bad address',
	17 => 'File exists',
	16 => 'Device or resource busy',
	19 => 'No such device',
	18 => 'Cross-device link',
	117 => 'Structure needs cleaning',
	116 => 'Stale NFS file handle',
	48 => 'Link number out of range',
	49 => 'Protocol driver not attached',
	46 => 'Level 3 halted',
	47 => 'Level 3 reset',
	44 => 'Channel number out of range',
	45 => 'Level 2 not synchronized',
	42 => 'No message of desired type',
	43 => 'Identifier removed',
	40 => 'Too many symbolic links encountered',
	118 => 'Not a XENIX named type file',
	1 => 'Operation not permitted',
	5 => 'I/O error',
	9 => 'Bad file number',
	77 => 'File descriptor in bad state',
	76 => 'Name not unique on network',
	75 => 'Value too large for defined data type',
	74 => 'Not a data message',
	73 => 'RFS specific error',
	72 => 'Multihop attempted',
	71 => 'Protocol error',
	70 => 'Communication error on send',
	79 => 'Can not access a needed shared library',
	78 => 'Remote address changed',
);

function strerror($i) {
	global $errno_int_to_str;
	if (array_key_exists($i, $errno_int_to_str))
		return $errno_int_to_str[$i];
	return null;
}

function errno_from_str($str) {
	global $errno_str_to_int;
	if (array_key_exists($str, $errno_str_to_int))
		return $errno_str_to_int[$str];
	return null;
}

function errno_from_last_error() {
	$e = error_get_last();
	list(, $msg) = explode('(): ', $e['message'], 2);
	return errno_from_str($msg);
}

?>
