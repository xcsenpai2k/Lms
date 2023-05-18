<?php

namespace App\Http\Controllers\Admin;

use App\Models\Question;
use App\Http\Controllers\Controller;
use App\Http\Requests\Question\QuestionRequest;
use App\Models\Answer;
use App\Models\Course;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class QuestionController extends Controller
{
    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function index()
    {
        return view('admin.questions.index');
    }

    /**
     *
     * @return DataTables
     */
    public function getQuestionData()
    {
        $questions = Question::select([
            'id',
            'content',
            'category',
            'answer',
            'score',
            'course_id'
        ])->with('course');

        return DataTables::of($questions)
            ->editColumn('course_id', function ($question) {
                return $question->course->title;
            })
            ->editColumn('category', function ($question) {
                switch ($question->category) {
                    case 0:
                        return 'Câu hỏi tự luận';
                    case 1:
                        return 'Câu hỏi nhiều lựa chọn';
                    case 2:
                        return 'Câu hỏi đúng sai';
                    case 3:
                        return 'Câu hỏi trắc nghiệm';
                    default:
                        return '';
                }
            })
            ->addColumn('answers', function ($question) {
                $tmp = <<<EOD
                <a onclick="event.preventDefault();answer_qu($question->id)"
                    href="" class="btn btn-primary btn-sm " title="Xem câu trả lời">
                    <i class="fa fa-plus-circle"></i></a>
EOD;
                switch ($question->category) {
                    case 0:
                        return 'Tự luận';
                    case 1:
                        return $tmp;
                    case 2:
                        return (1 == $question->answer) ? 'Đúng' : 'Sai';
                    case 3:
                        return $tmp;
                    default:
                        return '';
                }
            })
            ->addColumn('actions', function ($question) {
                return view('admin.questions.actions', ['row' => $question])->render();
            })
            ->rawColumns(['actions', 'answers', 'course_id'])
            ->make(true);
    }

    /**
     * @return \Illuminate\Contracts\View\View|\Illuminate\Contracts\View\Factory
     */
    public function create()
    {
        $course = Course::all();
        return view('admin.questions.create', compact('course'));
    }

    /**
     * @param QuestionRequest $request
     * @throws ModelNotFoundException
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function store(QuestionRequest $request)
    {
        $question_item = $request->except('_token');
        DB::beginTransaction();
        try {
            if ($question_item['category'] == 0) {
                $question = Question::create([
                    'content' => $question_item['content'],
                    'course_id' => $question_item['course_id'],
                    'category' => $question_item['category'],
                    'score' => $question_item['score']
                ]);
            } elseif ($question_item['category'] == 1) {
                $question = Question::create([
                    'content' => $question_item['content'],
                    'course_id' => $question_item['course_id'],
                    'category' => $question_item['category'],
                    'score' => $question_item['score']
                ]);

                $option = $request->input('answer1');
                $isCorrect = $request->input('is_correct');
                foreach ($option as $key => $content) {
                    Answer::create([
                        'question_id' => $question->id,
                        'content' => $content,
                        'checked' => array_key_exists($key, $isCorrect) ? 1 : 0
                    ]);
                }
            } elseif ($question_item['category'] == 3) {
                $question = Question::create([
                    'content' => $question_item['content'],
                    'course_id' => $question_item['course_id'],
                    'category' => $question_item['category'],
                    'score' => $question_item['score']
                ]);

                $option = $request->input('answer3');
                $isCorrect = $request->input('is_correct');
                for ($idx = 0; $idx < 4; $idx++) {
                    if ($option[$idx] != '') {
                        Answer::create([
                            'question_id' => $question->id,
                            'content' => $option[$idx],
                            'checked' => $isCorrect == $idx ? 1 : 0
                        ]);
                    }
                }
            } else {
                $question = Question::create([
                    'content' => $question_item['content'],
                    'course_id' => $question_item['course_id'],
                    'category' => $question_item['category'],
                    'score' => $question_item['score'],
                    'answer' => $question_item['answer2']
                ]);
            }
        } catch (\Exception $t) {
            DB::rollBack();
            // log error
            throw new \Exception($t->getMessage());
        }
        DB::commit();
        return redirect(route('question.index'))
            ->with('message', 'Thêm câu hỏi thành công !')
            ->with('type_alert', "success");
    }

    /**
     * @param Request $request
     * @param int $id
     *
     * @return \Illuminate\Contracts\View\View|\Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function edit(Request $request, $id)
    {
        $question = Question::find($id);
        if ($question) {
            if ($question->tests()->exists()) {
                return redirect(route('question.index'))
                    ->with('message', "Câu hỏi có trong bài test không thể sửa !")
                    ->with('type_alert', "danger");
            } else {
                $answers = Answer::where('question_id', $id)->get();
                $course = Course::all();

                return view('admin.questions.edit', compact(
                    'question',
                    'answers',
                    'course'
                ));
            }
        }
        return abort(404);
    }

    /**
     * @param QuestionRequest $request
     * @param integer $id
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function update(QuestionRequest $request, $id)
    {
        $msg = 'Câu hỏi không tồn tại !';
        $question = Question::find($id);
        if ($question->category == 1) {
            $question->content = $request->input('content');
            $question->course_id = $request->input('course_id');
            $question->score = $request->input('score');
            $question->save();
            $answers = Answer::where('question_id', $id)->get();
            $option = $request->input('answer1');
            $isCorrect = $request->input('is_correct');
            foreach ($answers as $key => $ans) {
                if ($option[$key] != '') {
                    $ans->content = $option[$key];
                    $ans->checked = isset($isCorrect[$key]) ? 1 : 0;
                    $ans->save();
                }
            }
            $msg = 'Sửa thành công câu hỏi :' . $question->content;
        } elseif ($question->category == 2) {
            $question->content = $request->input('content');
            $question->course_id = $request->input('course_id');
            $question->score = $request->input('score');
            $question->answer = $request->input('answer');
            $question->save();
            $msg = 'Sửa thành công câu hỏi :' . $question->content;
        } else {
            $question->content = $request->input('content');
            $question->course_id = $request->input('course_id');
            $question->score = $request->input('score');
            $question->save();
            $msg = 'Sửa thành công câu hỏi :' . $question->content;
        }

        return redirect(route('question.index'))
            ->with('message', $msg)
            ->with('type_alert', "success");
    }

    /**
     * @param Request $request
     * @throws ModelNotFoundException
     * @return \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request)
    {
        $question_id = 0 + $request->input('question_id', 0);
        $question = Question::find($question_id);
        if ($question) {
            if ($question->tests()->exists()) {
                return redirect(route('question.index'))
                    ->with('message', "Câu hỏi có trong bài test không thể xóa !")
                    ->with('type_alert', "danger");
            } else {
                if ($question_id) {
                    Question::destroy($question_id);
                    return redirect(route('question.index'))
                        ->with('message', "Xóa câu hỏi {$question_id} thành công !")
                        ->with('type_alert', "success");
                }
            }
        }
        throw new ModelNotFoundException();
    }

    /**
     * @param int $id
     * @return \Illuminate\Http\Response|\Illuminate\Contracts\Routing\ResponseFactory
     */
    public function show_answser($id)
    {
        $output = '';
        $answers = Answer::where('question_id', $id)->get();

        if ($answers->count()) {
            foreach ($answers as $an) {
                if ($an->checked == 1) {
                    $checked = 'Đúng';
                } else {
                    $checked = 'Sai';
                }

                $output .= '<tr>

                     <td class="text-center">' . $an->content . '</td>
                     <td class="text-center">' . $checked . '</td>

                     </tr>';
            }
        }
        return Response($output);
    }
}
