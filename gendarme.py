#!/usr/bin/env python
# -*- coding: utf-8 -*-

from signal import SIGTERM
from os import path, environ, waitpid, kill
from sys import argv, exit
from subprocess import call, Popen, PIPE


def kill_failed_processes(projects_path=path.curdir):
    """Kill failed process batches"""
    p = Popen(r"grep -hlm1 '^PHP Fatal error' %s/*/log/*" % projects_path,
              shell=True, stdout=PIPE).stdout
    if not p:
        exit(-4)
    for f in (path.realpath(v) for v in p.read().split('\n') if v):
        Popen(r"grep -a new\ slave %s | awk '{print $8}' | xargs kill" % f,
              shell=True).pid
        pm = Popen(r"grep -am1 new\ slave %s | awk '{print $2}'" % f,
                   shell=True, stdout=PIPE).stdout
        if pm:
            try:
                pid = int(pm.read().strip())
            except Exception:
                pass
            else:
                kill(pid, SIGTERM)
                call(('tail', '-f', f, '--pid', str(pid)))
            pm.close()
        yield f
    p.close()

def remove_lost_queues():
    """Clean up lost (stray) msg queues"""
    p = Popen(r'ps a -u`whoami` | grep start.php',
              shell=True, stdout=PIPE).stdout
    if not p:
        exit(-2)
    pids = [int(v.split(None, 1)[0]) for v in p.read().split('\n') if v]
    p.close()
    qids = []
    p = Popen(r'ipcs -qp | grep `whoami`', shell=True, stdout=PIPE).stdout
    if not p:
        exit(-1)
    qids = [q[0] for q in (v.split(None) for v in p.read().split('\n') if v)
            if not int(q[2]) in pids and not int(q[3]) in pids]
    p.close()
    if qids:
        call('ipcrm ' + ' '.join('-q%s' % qid for qid in qids), shell=True)

def restart_failed_processes():
    """Restart failed process batches"""
    log_files = tuple(kill_failed_processes(path.join(environ['HOME'],
                                                      'projects')))
    remove_lost_queues()
    for log_file in log_files:
        log_file_path, log_file_name = path.split(log_file)
        project_path = path.realpath(path.join(log_file_path, path.pardir))
        module_name = log_file_name.split(path.extsep, 1)[0]
        Popen(r'cd %(cwd)s && ( nohup php ./start.php %(project)s -c`grep -c new\ slave %(logfile)s` -m%(module)s &> %(logfile)s & ) && disown' % \
              {'cwd': project_path,
               'project': path.basename(project_path),
               'logfile': log_file,
               'module': module_name}, shell=True).pid


if '__main__' == __name__:
    handler = (1 < len(argv) and \
               locals().get(argv[1].lower().replace('-', '_'), None)) or None
    ((callable(handler) and handler) or restart_failed_processes)()
