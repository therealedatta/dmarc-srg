<?php

/**
 * dmarc-srg - A php parser, viewer and summary report generator for incoming DMARC reports.
 * Copyright (C) 2020 Aleksey Andreev (liuch)
 *
 * Available at:
 * https://github.com/liuch/dmarc-srg
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Liuch\DmarcSrg\Mail;

class MailBoxes
{
    private $box_list;

    public function __construct()
    {
        global $mailboxes;

        $this->box_list = [];
        if (is_array($mailboxes)) {
            $cnt = count($mailboxes);
            if ($cnt > 0) {
                if (isset($mailboxes[0])) {
                    for ($i = 0; $i < $cnt; ++$i) {
                        $this->box_list[] = new MailBox($mailboxes[$i]);
                    }
                } else {
                    $this->box_list[] = new MailBox($mailboxes);
                }
            }
        }
    }

    public function count()
    {
        return count($this->box_list);
    }

    public function list()
    {
        $id = 0;
        $res = [];
        foreach ($this->box_list as &$mbox) {
            $id += 1;
            $res[] = [
                'id'      => $id,
                'name'    => $mbox->name(),
                'host'    => $mbox->host(),
                'mailbox' => $mbox->mailbox()
            ];
        }
        unset($mbox);
        return $res;
    }

    public function mailbox($id)
    {
        if (!is_int($id) || $id < 0 || $id > count($this->box_list)) {
            throw new \Exception('Incorrect mailbox Id', -1);
        }
        return $this->box_list[$id - 1];
    }

    public function check($id)
    {
        if ($id !== 0) {
            try {
                return $this->mailbox($id)->check();
            } catch (\Exception $e) {
                return [
                    'error_code' => $e->getCode(),
                    'message' => $e->getMessage()
                ];
            }
        }

        $results = [];
        $err_cnt = 0;
        $box_cnt = count($this->box_list);
        for ($i = 0; $i < $box_cnt; ++$i) {
            $r = $this->box_list[$i]->check();
            if ($r['error_code'] !== 0) {
                ++$err_cnt;
            }
            $results[] = $r;
        }
        $res = [];
        if ($err_cnt == 0) {
            $res['error_code'] = 0;
            $res['message'] = 'Success';
        } else {
            $res['error_code'] = -1;
            $res['message'] = sprintf('%d of the %d mailboxes failed the check', $err_cnt, $box_cnt);
        }
        $res['results'] = $results;
        return $res;
    }
}
