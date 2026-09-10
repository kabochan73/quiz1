<?php

namespace App\Enums;

/**
 * questions.reference_answer_source の値。
 *
 * 参考解答（AI 生成の解答例）の「出所」を表す。
 * 採点では参考解答を「表現の一例」としてしか使わないので、
 * この値は主に UI 表示（「AI 生成」「編集済み」バッジなど）と、生成フローの制御に使う。
 */
enum ReferenceAnswerSource: string
{
    /** まだ生成していない。 */
    case None = 'none';

    /** AI が生成したまま、未編集。 */
    case Ai = 'ai';

    /** AI 生成後、ユーザーが手直しした。 */
    case AiEdited = 'ai_edited';

    /** 参考解答が存在する（None 以外）か。 */
    public function exists(): bool
    {
        return $this !== self::None;
    }
}
