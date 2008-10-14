/* tube.c - tubes implementation */

/* Copyright (C) 2008 Keith Rarick and Philotic Inc.

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

#include "stat.h"
#include "tube.h"
#include "prot.h"
#include "util.h"

tube
make_tube(const char *name)
{
    tube t;

    t = malloc(sizeof(struct tube));
    if (!t) return NULL;

    t->refs = 0;

    t->name[MAX_TUBE_NAME_LEN - 1] = '\0';
    strncpy(t->name, name, MAX_TUBE_NAME_LEN - 1);
    if (t->name[MAX_TUBE_NAME_LEN - 1] != '\0') twarnx("truncating tube name");

    pq_init(&t->ready, job_pri_cmp);
    pq_init(&t->delay, job_delay_cmp);
    t->buried = (struct job) { &t->buried, &t->buried, 0 };
    ms_init(&t->waiting, NULL, NULL);

    t->stat = (struct stats) {0, 0, 0, 0};
    t->using_ct = t->watching_ct = 0;

    return t;
}

static void
tube_free(tube t)
{
    prot_remove_tube(t);
    pq_clear(&t->ready);
    ms_clear(&t->waiting);
    free(t);
}

void
tube_dref(tube t)
{
    if (!t) return;
    if (t->refs < 1) return twarnx("refs is zero for tube: %s", t->name);

    --t->refs;
    if (t->refs < 1) tube_free(t);
}

void
tube_iref(tube t)
{
    if (!t) return;
    ++t->refs;
}
