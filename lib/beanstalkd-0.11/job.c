/* job.c - a job in the queue */

/* Copyright (C) 2007 Keith Rarick and Philotic Inc.

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

#include <stdlib.h>
#include <string.h>

#include "tube.h"
#include "job.h"
#include "util.h"

static unsigned long long int next_id = 1;

job
allocate_job(int body_size)
{
    job j;

    j = malloc(sizeof(struct job) + body_size);
    if (!j) return twarnx("OOM"), NULL;

    j->state = JOB_STATE_INVALID;
    j->creation = time(NULL);
    j->timeout_ct = j->release_ct = j->bury_ct = j->kick_ct = 0;
    j->body_size = body_size;
    j->next = j->prev = j; /* not in a linked list */
    j->tube = NULL;

    return j;
}

job
make_job(unsigned int pri, unsigned int delay, unsigned int ttr, int body_size,
         tube tube)
{
    job j;

    j = allocate_job(body_size);
    if (!j) return twarnx("OOM"), NULL;

    j->id = next_id++;
    j->pri = pri;
    j->delay = delay;
    j->ttr = ttr;
    TUBE_ASSIGN(j->tube, tube);

    return j;
}

void
job_free(job j)
{
    if (j) TUBE_ASSIGN(j->tube, NULL);
    free(j);
}

int
job_pri_cmp(job a, job b)
{
    if (a->pri == b->pri) {
        /* we can't just subtract because id has too many bits */
        if (a->id > b->id) return 1;
        if (a->id < b->id) return -1;
        return 0;
    }
    return a->pri - b->pri;
}

int
job_delay_cmp(job a, job b)
{
    if (a->deadline == b->deadline) {
        /* we can't just subtract because id has too many bits */
        if (a->id > b->id) return 1;
        if (a->id < b->id) return -1;
        return 0;
    }
    return a->deadline - b->deadline;
}

job
job_copy(job j)
{
    job n;

    if (!j) return NULL;

    n = malloc(sizeof(struct job) + j->body_size);
    if (!n) return twarnx("OOM"), NULL;

    memcpy(n, j, sizeof(struct job) + j->body_size);
    n->next = n->prev = n; /* not in a linked list */

    n->tube = 0; /* Don't use memcpy for the tube, which we must refcount. */
    TUBE_ASSIGN(n->tube, j->tube);

    return n;
}

const char *
job_state(job j)
{
    if (j->state == JOB_STATE_READY) return "ready";
    if (j->state == JOB_STATE_RESERVED) return "reserved";
    if (j->state == JOB_STATE_BURIED) return "buried";
    if (j->state == JOB_STATE_DELAYED) return "delayed";
    return "invalid";
}

int
job_list_any_p(job head)
{
    return head->next != head || head->prev != head;
}

job
job_remove(job j)
{
    if (!j) return NULL;
    if (!job_list_any_p(j)) return NULL; /* not in a doubly-linked list */

    j->next->prev = j->prev;
    j->prev->next = j->next;

    j->prev = j->next = j;

    return j;
}

void
job_insert(job head, job j)
{
    if (job_list_any_p(j)) return; /* already in a linked list */

    j->prev = head->prev;
    j->next = head;
    head->prev->next = j;
    head->prev = j;
}

unsigned long long int
total_jobs()
{
    return next_id - 1;
}
