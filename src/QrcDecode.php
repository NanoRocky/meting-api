<?php
namespace QrcDecode;

// source from:
// https://github.com/luren-dc/QQMusicApi/blob/main/qqmusic_api/utils/tripledes.py
// https://github.com/luren-dc/QQMusicApi/blob/main/qqmusic_api/utils/common.py#L86
class Decoder
{
    public function __construct() {}
    private $en_mode = 1;
    private $de_mode = 0;

    private $sbox = [
        # sbox1
        [
            14,
            4,
            13,
            1,
            2,
            15,
            11,
            8,
            3,
            10,
            6,
            12,
            5,
            9,
            0,
            7,
            0,
            15,
            7,
            4,
            14,
            2,
            13,
            1,
            10,
            6,
            12,
            11,
            9,
            5,
            3,
            8,
            4,
            1,
            14,
            8,
            13,
            6,
            2,
            11,
            15,
            12,
            9,
            7,
            3,
            10,
            5,
            0,
            15,
            12,
            8,
            2,
            4,
            9,
            1,
            7,
            5,
            11,
            3,
            14,
            10,
            0,
            6,
            13,
        ],

        # sbox2
        [
            15,
            1,
            8,
            14,
            6,
            11,
            3,
            4,
            9,
            7,
            2,
            13,
            12,
            0,
            5,
            10,
            3,
            13,
            4,
            7,
            15,
            2,
            8,
            15,
            12,
            0,
            1,
            10,
            6,
            9,
            11,
            5,
            0,
            14,
            7,
            11,
            10,
            4,
            13,
            1,
            5,
            8,
            12,
            6,
            9,
            3,
            2,
            15,
            13,
            8,
            10,
            1,
            3,
            15,
            4,
            2,
            11,
            6,
            7,
            12,
            0,
            5,
            14,
            9,
        ],

        # sbox3
        [
            10,
            0,
            9,
            14,
            6,
            3,
            15,
            5,
            1,
            13,
            12,
            7,
            11,
            4,
            2,
            8,
            13,
            7,
            0,
            9,
            3,
            4,
            6,
            10,
            2,
            8,
            5,
            14,
            12,
            11,
            15,
            1,
            13,
            6,
            4,
            9,
            8,
            15,
            3,
            0,
            11,
            1,
            2,
            12,
            5,
            10,
            14,
            7,
            1,
            10,
            13,
            0,
            6,
            9,
            8,
            7,
            4,
            15,
            14,
            3,
            11,
            5,
            2,
            12,
        ],

        # sbox4
        [
            7,
            13,
            14,
            3,
            0,
            6,
            9,
            10,
            1,
            2,
            8,
            5,
            11,
            12,
            4,
            15,
            13,
            8,
            11,
            5,
            6,
            15,
            0,
            3,
            4,
            7,
            2,
            12,
            1,
            10,
            14,
            9,
            10,
            6,
            9,
            0,
            12,
            11,
            7,
            13,
            15,
            1,
            3,
            14,
            5,
            2,
            8,
            4,
            3,
            15,
            0,
            6,
            10,
            10,
            13,
            8,
            9,
            4,
            5,
            11,
            12,
            7,
            2,
            14,
        ],

        # sbox5
        [
            2,
            12,
            4,
            1,
            7,
            10,
            11,
            6,
            8,
            5,
            3,
            15,
            13,
            0,
            14,
            9,
            14,
            11,
            2,
            12,
            4,
            7,
            13,
            1,
            5,
            0,
            15,
            10,
            3,
            9,
            8,
            6,
            4,
            2,
            1,
            11,
            10,
            13,
            7,
            8,
            15,
            9,
            12,
            5,
            6,
            3,
            0,
            14,
            11,
            8,
            12,
            7,
            1,
            14,
            2,
            13,
            6,
            15,
            0,
            9,
            10,
            4,
            5,
            3,
        ],

        # sbox6
        [
            12,
            1,
            10,
            15,
            9,
            2,
            6,
            8,
            0,
            13,
            3,
            4,
            14,
            7,
            5,
            11,
            10,
            15,
            4,
            2,
            7,
            12,
            9,
            5,
            6,
            1,
            13,
            14,
            0,
            11,
            3,
            8,
            9,
            14,
            15,
            5,
            2,
            8,
            12,
            3,
            7,
            0,
            4,
            10,
            1,
            13,
            11,
            6,
            4,
            3,
            2,
            12,
            9,
            5,
            15,
            10,
            11,
            14,
            1,
            7,
            6,
            0,
            8,
            13,
        ],

        # sbox7
        [
            4,
            11,
            2,
            14,
            15,
            0,
            8,
            13,
            3,
            12,
            9,
            7,
            5,
            10,
            6,
            1,
            13,
            0,
            11,
            7,
            4,
            9,
            1,
            10,
            14,
            3,
            5,
            12,
            2,
            15,
            8,
            6,
            1,
            4,
            11,
            13,
            12,
            3,
            7,
            14,
            10,
            15,
            6,
            8,
            0,
            5,
            9,
            2,
            6,
            11,
            13,
            8,
            1,
            4,
            10,
            7,
            9,
            5,
            0,
            15,
            14,
            2,
            3,
            12,
        ],

        # sbox8
        [
            13,
            2,
            8,
            4,
            6,
            15,
            11,
            1,
            10,
            9,
            3,
            14,
            5,
            0,
            12,
            7,
            1,
            15,
            13,
            8,
            10,
            3,
            7,
            4,
            12,
            5,
            6,
            11,
            0,
            14,
            9,
            2,
            7,
            11,
            4,
            1,
            9,
            12,
            14,
            2,
            0,
            6,
            10,
            13,
            15,
            3,
            5,
            8,
            2,
            1,
            14,
            7,
            4,
            10,
            8,
            13,
            15,
            12,
            9,
            0,
            3,
            5,
            6,
            11,
        ],
    ];

    private function bitnum(string $a, int $b, int $c): int
    {
        $index = intdiv($b, 32) * 4 + 3 - intdiv($b % 32, 8);
        $byte = ord($a[$index]);
        $shift = 7 - ($b % 8);
        return (($byte >> $shift) & 1) << $c;
    }

    /**
     * 从整数中提取指定位置的位,并左移指定偏移量。
     *
     * @param int $a 整数
     * @param int $b 要提取的位索引
     * @param int $c 位提取后的偏移量
     * @return int 提取后的位
     */
    private function bitnum_intr(int $a, int $b, int $c): int
    {
        return (($a >> 31 - $b) & 1) << $c;
    }

    private function bitnum_intl(int $a, int $b, int $c): int
    {
        return (($a << $b) & 0x80000000) >> $c;
    }

    private function sbox_bit(int $a): int
    {
        // 对输入整数进行位运算,重新组合位。
        // :param a: 整数
        // :return: 重新组合后的位
        return ($a & 32) | (($a & 31) >> 1) | (($a & 1) << 4);
    }

    private function initial_permutation(string $input_data): array
    {
        $s0 =
            $this->bitnum($input_data, 57, 31) |
            $this->bitnum($input_data, 49, 30) |
            $this->bitnum($input_data, 41, 29) |
            $this->bitnum($input_data, 33, 28) |
            $this->bitnum($input_data, 25, 27) |
            $this->bitnum($input_data, 17, 26) |
            $this->bitnum($input_data, 9, 25) |
            $this->bitnum($input_data, 1, 24) |
            $this->bitnum($input_data, 59, 23) |
            $this->bitnum($input_data, 51, 22) |
            $this->bitnum($input_data, 43, 21) |
            $this->bitnum($input_data, 35, 20) |
            $this->bitnum($input_data, 27, 19) |
            $this->bitnum($input_data, 19, 18) |
            $this->bitnum($input_data, 11, 17) |
            $this->bitnum($input_data, 3, 16) |
            $this->bitnum($input_data, 61, 15) |
            $this->bitnum($input_data, 53, 14) |
            $this->bitnum($input_data, 45, 13) |
            $this->bitnum($input_data, 37, 12) |
            $this->bitnum($input_data, 29, 11) |
            $this->bitnum($input_data, 21, 10) |
            $this->bitnum($input_data, 13, 9) |
            $this->bitnum($input_data, 5, 8) |
            $this->bitnum($input_data, 63, 7) |
            $this->bitnum($input_data, 55, 6) |
            $this->bitnum($input_data, 47, 5) |
            $this->bitnum($input_data, 39, 4) |
            $this->bitnum($input_data, 31, 3) |
            $this->bitnum($input_data, 23, 2) |
            $this->bitnum($input_data, 15, 1) |
            $this->bitnum($input_data, 7, 0);

        $s1 =
            $this->bitnum($input_data, 56, 31) |
            $this->bitnum($input_data, 48, 30) |
            $this->bitnum($input_data, 40, 29) |
            $this->bitnum($input_data, 32, 28) |
            $this->bitnum($input_data, 24, 27) |
            $this->bitnum($input_data, 16, 26) |
            $this->bitnum($input_data, 8, 25) |
            $this->bitnum($input_data, 0, 24) |
            $this->bitnum($input_data, 58, 23) |
            $this->bitnum($input_data, 50, 22) |
            $this->bitnum($input_data, 42, 21) |
            $this->bitnum($input_data, 34, 20) |
            $this->bitnum($input_data, 26, 19) |
            $this->bitnum($input_data, 18, 18) |
            $this->bitnum($input_data, 10, 17) |
            $this->bitnum($input_data, 2, 16) |
            $this->bitnum($input_data, 60, 15) |
            $this->bitnum($input_data, 52, 14) |
            $this->bitnum($input_data, 44, 13) |
            $this->bitnum($input_data, 36, 12) |
            $this->bitnum($input_data, 28, 11) |
            $this->bitnum($input_data, 20, 10) |
            $this->bitnum($input_data, 12, 9) |
            $this->bitnum($input_data, 4, 8) |
            $this->bitnum($input_data, 62, 7) |
            $this->bitnum($input_data, 54, 6) |
            $this->bitnum($input_data, 46, 5) |
            $this->bitnum($input_data, 38, 4) |
            $this->bitnum($input_data, 30, 3) |
            $this->bitnum($input_data, 22, 2) |
            $this->bitnum($input_data, 14, 1) |
            $this->bitnum($input_data, 6, 0);

        return [$s0, $s1];
    }

    private function inverse_permutation(int $s0, int $s1): string
    {
        $data = array_fill(0, 8, 0);

        $data[3] =
            $this->bitnum_intr($s1, 7, 7) |
            $this->bitnum_intr($s0, 7, 6) |
            $this->bitnum_intr($s1, 15, 5) |
            $this->bitnum_intr($s0, 15, 4) |
            $this->bitnum_intr($s1, 23, 3) |
            $this->bitnum_intr($s0, 23, 2) |
            $this->bitnum_intr($s1, 31, 1) |
            $this->bitnum_intr($s0, 31, 0);

        $data[2] =
            $this->bitnum_intr($s1, 6, 7) |
            $this->bitnum_intr($s0, 6, 6) |
            $this->bitnum_intr($s1, 14, 5) |
            $this->bitnum_intr($s0, 14, 4) |
            $this->bitnum_intr($s1, 22, 3) |
            $this->bitnum_intr($s0, 22, 2) |
            $this->bitnum_intr($s1, 30, 1) |
            $this->bitnum_intr($s0, 30, 0);

        $data[1] =
            $this->bitnum_intr($s1, 5, 7) |
            $this->bitnum_intr($s0, 5, 6) |
            $this->bitnum_intr($s1, 13, 5) |
            $this->bitnum_intr($s0, 13, 4) |
            $this->bitnum_intr($s1, 21, 3) |
            $this->bitnum_intr($s0, 21, 2) |
            $this->bitnum_intr($s1, 29, 1) |
            $this->bitnum_intr($s0, 29, 0);

        $data[0] =
            $this->bitnum_intr($s1, 4, 7) |
            $this->bitnum_intr($s0, 4, 6) |
            $this->bitnum_intr($s1, 12, 5) |
            $this->bitnum_intr($s0, 12, 4) |
            $this->bitnum_intr($s1, 20, 3) |
            $this->bitnum_intr($s0, 20, 2) |
            $this->bitnum_intr($s1, 28, 1) |
            $this->bitnum_intr($s0, 28, 0);

        $data[7] =
            $this->bitnum_intr($s1, 3, 7) |
            $this->bitnum_intr($s0, 3, 6) |
            $this->bitnum_intr($s1, 11, 5) |
            $this->bitnum_intr($s0, 11, 4) |
            $this->bitnum_intr($s1, 19, 3) |
            $this->bitnum_intr($s0, 19, 2) |
            $this->bitnum_intr($s1, 27, 1) |
            $this->bitnum_intr($s0, 27, 0);

        $data[6] =
            $this->bitnum_intr($s1, 2, 7) |
            $this->bitnum_intr($s0, 2, 6) |
            $this->bitnum_intr($s1, 10, 5) |
            $this->bitnum_intr($s0, 10, 4) |
            $this->bitnum_intr($s1, 18, 3) |
            $this->bitnum_intr($s0, 18, 2) |
            $this->bitnum_intr($s1, 26, 1) |
            $this->bitnum_intr($s0, 26, 0);

        $data[5] =
            $this->bitnum_intr($s1, 1, 7) |
            $this->bitnum_intr($s0, 1, 6) |
            $this->bitnum_intr($s1, 9, 5) |
            $this->bitnum_intr($s0, 9, 4) |
            $this->bitnum_intr($s1, 17, 3) |
            $this->bitnum_intr($s0, 17, 2) |
            $this->bitnum_intr($s1, 25, 1) |
            $this->bitnum_intr($s0, 25, 0);

        $data[4] =
            $this->bitnum_intr($s1, 0, 7) |
            $this->bitnum_intr($s0, 0, 6) |
            $this->bitnum_intr($s1, 8, 5) |
            $this->bitnum_intr($s0, 8, 4) |
            $this->bitnum_intr($s1, 16, 3) |
            $this->bitnum_intr($s0, 16, 2) |
            $this->bitnum_intr($s1, 24, 1) |
            $this->bitnum_intr($s0, 24, 0);

        return pack("C8", ...$data);
    }

    private function f(int $state, array $key): int
    {
        // global $sbox;
        $t1 =
            $this->bitnum_intl($state, 31, 0) |
            (($state & 0xf0000000) >> 1) |
            $this->bitnum_intl($state, 4, 5) |
            $this->bitnum_intl($state, 3, 6) |
            (($state & 0x0f000000) >> 3) |
            $this->bitnum_intl($state, 8, 11) |
            $this->bitnum_intl($state, 7, 12) |
            (($state & 0x00f00000) >> 5) |
            $this->bitnum_intl($state, 12, 17) |
            $this->bitnum_intl($state, 11, 18) |
            (($state & 0x000f0000) >> 7) |
            $this->bitnum_intl($state, 16, 23);

        $t2 =
            $this->bitnum_intl($state, 15, 0) |
            (($state & 0x0000f000) << 15) |
            $this->bitnum_intl($state, 20, 5) |
            $this->bitnum_intl($state, 19, 6) |
            (($state & 0x00000f00) << 13) |
            $this->bitnum_intl($state, 24, 11) |
            $this->bitnum_intl($state, 23, 12) |
            (($state & 0x000000f0) << 11) |
            $this->bitnum_intl($state, 28, 17) |
            $this->bitnum_intl($state, 27, 18) |
            (($state & 0x0000000f) << 9) |
            $this->bitnum_intl($state, 0, 23);

        $lrgstate = [
            ($t1 >> 24) & 0x000000ff,
            ($t1 >> 16) & 0x000000ff,
            ($t1 >> 8) & 0x000000ff,
            ($t2 >> 24) & 0x000000ff,
            ($t2 >> 16) & 0x000000ff,
            ($t2 >> 8) & 0x000000ff,
        ];

        for ($i = 0; $i < 6; $i++) {
            $lrgstate[$i] ^= $key[$i];
        }

        $state =
            ($this->sbox[0][$this->sbox_bit($lrgstate[0] >> 2)] << 28) |
            ($this->sbox[1][
                $this->sbox_bit((($lrgstate[0] & 0x03) << 4) | ($lrgstate[1] >> 4))
            ] <<
                24) |
            ($this->sbox[2][
                $this->sbox_bit((($lrgstate[1] & 0x0f) << 2) | ($lrgstate[2] >> 6))
            ] <<
                20) |
            ($this->sbox[3][$this->sbox_bit($lrgstate[2] & 0x3f)] << 16) |
            ($this->sbox[4][$this->sbox_bit($lrgstate[3] >> 2)] << 12) |
            ($this->sbox[5][
                $this->sbox_bit((($lrgstate[3] & 0x03) << 4) | ($lrgstate[4] >> 4))
            ] <<
                8) |
            ($this->sbox[6][
                $this->sbox_bit((($lrgstate[4] & 0x0f) << 2) | ($lrgstate[5] >> 6))
            ] <<
                4) |
            $this->sbox[7][$this->sbox_bit($lrgstate[5] & 0x3f)];

        return $this->bitnum_intl($state, 15, 0) |
            $this->bitnum_intl($state, 6, 1) |
            $this->bitnum_intl($state, 19, 2) |
            $this->bitnum_intl($state, 20, 3) |
            $this->bitnum_intl($state, 28, 4) |
            $this->bitnum_intl($state, 11, 5) |
            $this->bitnum_intl($state, 27, 6) |
            $this->bitnum_intl($state, 16, 7) |
            $this->bitnum_intl($state, 0, 8) |
            $this->bitnum_intl($state, 14, 9) |
            $this->bitnum_intl($state, 22, 10) |
            $this->bitnum_intl($state, 25, 11) |
            $this->bitnum_intl($state, 4, 12) |
            $this->bitnum_intl($state, 17, 13) |
            $this->bitnum_intl($state, 30, 14) |
            $this->bitnum_intl($state, 9, 15) |
            $this->bitnum_intl($state, 1, 16) |
            $this->bitnum_intl($state, 7, 17) |
            $this->bitnum_intl($state, 23, 18) |
            $this->bitnum_intl($state, 13, 19) |
            $this->bitnum_intl($state, 31, 20) |
            $this->bitnum_intl($state, 26, 21) |
            $this->bitnum_intl($state, 2, 22) |
            $this->bitnum_intl($state, 8, 23) |
            $this->bitnum_intl($state, 18, 24) |
            $this->bitnum_intl($state, 12, 25) |
            $this->bitnum_intl($state, 29, 26) |
            $this->bitnum_intl($state, 5, 27) |
            $this->bitnum_intl($state, 21, 28) |
            $this->bitnum_intl($state, 10, 29) |
            $this->bitnum_intl($state, 3, 30) |
            $this->bitnum_intl($state, 24, 31);
    }

    private function acrypt(string $input_data, array $key): string
    {
        list($s0, $s1) = $this->initial_permutation($input_data); // 初始置换

        for ($idx = 0; $idx < 15; $idx++) {
            // 15轮迭代
            $previous_s1 = $s1;
            $s1 = $this->f($s1, $key[$idx]) ^ $s0;
            $s0 = $previous_s1;
        }
        $s0 = $this->f($s1, $key[15]) ^ $s0; // 第15轮

        return $this->inverse_permutation($s0, $s1); // 逆置换
    }

    private function key_schedule(string $key, int $mode): array
    {
        $schedule = array_fill(0, 16, array_fill(0, 6, 0));
        $key_rnd_shift = [1, 1, 2, 2, 2, 2, 2, 2, 1, 2, 2, 2, 2, 2, 2, 1];
        $key_perm_c = [
            56,
            48,
            40,
            32,
            24,
            16,
            8,
            0,
            57,
            49,
            41,
            33,
            25,
            17,
            9,
            1,
            58,
            50,
            42,
            34,
            26,
            18,
            10,
            2,
            59,
            51,
            43,
            35,
        ];
        $key_perm_d = [
            62,
            54,
            46,
            38,
            30,
            22,
            14,
            6,
            61,
            53,
            45,
            37,
            29,
            21,
            13,
            5,
            60,
            52,
            44,
            36,
            28,
            20,
            12,
            4,
            27,
            19,
            11,
            3,
        ];
        $key_compression = [
            13,
            16,
            10,
            23,
            0,
            4,
            2,
            27,
            14,
            5,
            20,
            9,
            22,
            18,
            11,
            3,
            25,
            7,
            15,
            6,
            26,
            19,
            12,
            1,
            40,
            51,
            30,
            36,
            46,
            54,
            29,
            39,
            50,
            44,
            32,
            47,
            43,
            48,
            38,
            55,
            33,
            52,
            45,
            41,
            49,
            35,
            28,
            31,
        ];

        $c = 0;
        for ($i = 0; $i < 28; $i++) {
            $c += $this->bitnum($key, $key_perm_c[$i], 31 - $i);
        }
        $d = 0;
        for ($i = 0; $i < 28; $i++) {
            $d += $this->bitnum($key, $key_perm_d[$i], 31 - $i);
        }

        for ($i = 0; $i < 16; $i++) {
            $shift = $key_rnd_shift[$i];
            $c = (($c << $shift) | ($c >> 28 - $shift)) & 0xfffffff0;
            $d = (($d << $shift) | ($d >> 28 - $shift)) & 0xfffffff0;

            $togen = $mode === 0 ? 15 - $i : $i;

            for ($j = 0; $j < 6; $j++) {
                $schedule[$togen][$j] = 0;
            }

            for ($j = 0; $j < 24; $j++) {
                $schedule[$togen][intdiv($j, 8)] |= $this->bitnum_intr(
                    $c,
                    $key_compression[$j],
                    7 - ($j % 8)
                );
            }

            for ($j = 24; $j < 48; $j++) {
                $schedule[$togen][intdiv($j, 8)] |= $this->bitnum_intr(
                    $d,
                    $key_compression[$j] - 27,
                    7 - ($j % 8)
                );
            }
        }
        return $schedule;
    }

    private function tripledes_key_setup(string $key, int $mode): array
    {
        // global $en_mode, $de_mode;
        if ($mode === $this->en_mode) {
            return [
                $this->key_schedule(substr($key, 0), $this->en_mode),
                $this->key_schedule(substr($key, 8), $this->de_mode),
                $this->key_schedule(substr($key, 16), $this->en_mode),
            ];
        }
        return [
            $this->key_schedule(substr($key, 16), $this->de_mode),
            $this->key_schedule(substr($key, 8), $this->en_mode),
            $this->key_schedule(substr($key, 0), $this->de_mode),
        ];
    }

    private function tripledes_crypt(string $data, array $keys): string
    {
        for ($i = 0; $i < 3; $i++) {
            $data = $this->acrypt($data, $keys[$i]);
        }
        return $data;
    }

    public function decode(string $hex): string
    {
        $schedule = $this->tripledes_key_setup(
            "!@#)(*$%123ZXC!@!@#)(NHL",
            $this->de_mode
        );
        $data = "";
        $bin = hex2bin($hex);
        for ($i = 0, $len = strlen($bin); $i < $len; $i += 8) {
            $data .= $this->tripledes_crypt(substr($bin, $i, 8), $schedule);
        }
        return zlib_decode($data);
    }
}
?>
