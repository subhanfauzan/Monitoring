<?php

namespace App\Imports;

use App\Models\Tiket;
use App\Models\Dapot;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ImportAlarm implements ToModel, WithHeadingRow
{
    /**
     * Tentukan baris mana yang menjadi header (baris ke-6)
     */
    public function headingRow(): int
    {
        return 6;
    }

    public function model(array $row)
    {
        // Ambil data dengan antisipasi header yang disnake-case oleh Maatwebsite Excel
        $firstOccurred = $row['first_occurred_nt'] ?? $this->findInRow($row, 'first_occurred');
        $moName = $row['mo_name'] ?? $this->findInRow($row, 'mo_name');
        $comment = $row['comment'] ?? $this->findInRow($row, 'comment');

        // Jika semua kolom penting kosong, kemungkinan baris kosong
        if (empty($firstOccurred) && empty($moName) && empty($comment)) {
            return null;
        }

        $parsedComment = $this->parseComment($comment);
        $city = $parsedComment['city'];
        $ticketSwfm = $parsedComment['ticket_swfm'];
        $remark = $parsedComment['remark'];
        $dapotInfo = $this->resolveDapotFromCity($city);
        $nop = $dapotInfo['nop'];
        $clusterTo = $dapotInfo['cluster_to'];

        // Pencegahan Duplikat
        $exists = Tiket::where('time_down', $firstOccurred)
            ->where('site_id', $moName)
            ->where('ticket_swfm', $ticketSwfm)
            ->where('remark', $remark)
            ->exists();

        if ($exists) {
            return null; // Skip insert
        }

        return new Tiket([
            'site_id'         => $moName,
            'site_class'      => '-',
            'saverity'        => 'Low',
            'suspect_problem' => 'Power',
            'time_down'       => $firstOccurred,
            'status_site'     => 'Down',
            'tim_fop'         => null,
            'remark'          => $remark,
            'ticket_swfm'     => $ticketSwfm,
            'nop'             => $nop,
            'cluster_to'      => $clusterTo,
            'nossa'           => '-',
            'status_ticket'   => 'Open',
        ]);
    }

    /**
     * Memisahkan kota, ticket_swfm, dan remark dari Comment
     */
    public function parseComment($comment)
    {
        if (empty($comment) || trim($comment) === '-') {
            return ['city' => null, 'ticket_swfm' => '-', 'remark' => '-'];
        }

        // Pecah jadi maksimal 3 bagian: [0] kota, [1] potensi IM-, [2] sisa text
        $parts = explode(' ', trim($comment), 3);
        
        $city = trim($parts[0]);
        $ticket_swfm = '-';
        $remark = '-';

        if (isset($parts[1])) {
            if (str_starts_with(trim($parts[1]), 'IM-')) {
                $ticket_swfm = trim($parts[1]);
                $remark = isset($parts[2]) ? trim($parts[2]) : '-';
            } else {
                $remainder = isset($parts[2]) ? $parts[1] . ' ' . $parts[2] : $parts[1];
                $remark = trim($remainder);
            }
        }

        return [
            'city'        => $city,
            'ticket_swfm' => $ticket_swfm,
            'remark'      => $remark
        ];
    }

    /**
     * Resolve NOP & Cluster dari nama kota
     */
    public function resolveDapotFromCity($city)
    {
        if (!$city) {
            return ['nop' => 'JATIM', 'cluster_to' => '-'];
        }

        $dapot = Dapot::where('nop', 'LIKE', '%' . $city . '%')->first();
        
        if ($dapot && !empty($dapot->nop)) {
            return [
                'nop'        => $dapot->nop,
                'cluster_to' => $dapot->cluster_to ?? '-'
            ];
        }

        return ['nop' => 'JATIM', 'cluster_to' => '-'];
    }

    /**
     * Helper untuk fallback pencarian key di array
     */
    private function findInRow($row, $keyword)
    {
        foreach ($row as $key => $value) {
            if (Str::contains((string)$key, $keyword)) {
                return $value;
            }
        }
        return null;
    }
}
