<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use App\Http\Request\BooknigRequest;
use App\Http\Request\JobRequest;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index()
    {
        if($user_id = request()->user_id) {

            $response = $this->repository->getUsersJobs($user_id);

        }
        elseif(request()->__authenticatedUser->user_type == config('app.ADMIN_ROLE_ID') || request()->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))
        {
            $response = $this->repository->getAll(request()->all());
        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {
        $job = $this->repository->with('translatorJobRel.user')->find($id);
        if($job){
            return response($job);
        }
        return null;
    }

    /**
     * @param BooknigRequest $request
     * @return mixed
     */
    public function store(BooknigRequest $request)
    {
        if($data = $request->validated()){
            $response = $this->repository->store($data->__authenticatedUser, $data);
            if($response){

                return response($response);
            }
            return null;
        }

    }

    /**
     * @param $id
     * @param BookingRequest $request
     * @return mixed
     */
    public function update($id, BookingRequest $request)
    {
        if($data = $request->validated()){
            $cuser = $data->__authenticatedUser;
            $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);
            if($response){

                return response($response);
            }
            return null;
        }
    }

    /**
     * @param JobRequest $request
     * @return mixed
     */
    public function immediateJobEmail(JobRequest $request)
    {
        if($data = $request->validated()){
            $response = $this->repository->storeJobEmail($data);
            if($response){

                return response($response);
            }
            return null;
        }
    }

    /**
     * @return mixed
     */
    public function getHistory()
    {
        if($user_id = request()->user_id) {

            $response = $this->repository->getUsersJobsHistory($user_id, request());
            if($response){

                return response($response);
            }
            return null;
        }

        return null;
    }

    /**
     * @param JobRequest $request
     * @return mixed
     */
    public function acceptJob(JobRequest $request)
    {
        if($data = $request->validated()){
            $response = $this->repository->acceptJob($data, $request->__authenticatedUser);
            if($response){
                return response($response);
            }
            return null;
        }
    }

    public function acceptJobWithId(JobRequest $request)
    {
        if($data = $request->validated()){
            $response = $this->repository->acceptJobWithId($data, $request->__authenticatedUser);
            if($response){
                return response($response);
            }
            return null;
        }
    }

    /**
     * @param JobRequest $request
     * @return mixed
     */
    public function cancelJob(JobRequest $request)
    {
        if($data = $request->validated()){
            $response = $this->repository->cancelJobAjax($data, $data->__authenticatedUser);
            if($response){
                return response($response);
            }
            return null;
        }
    }

    /**
     * @param JobRequest $request
     * @return mixed
     */
    public function endJob(JobRequest $request)
    {
        if($data = $request->validated()) {
            $response = $this->repository->endJob($data);
            if($response){

                return response($response);
            }
            return null;
        }

    }

    public function customerNotCall(CustomerRequest $request)
    {
        if($data = $request->validated()) {
            $response = $this->repository->customerNotCall($data);
            if($response){

                return response($response);
            }
            return null;
        }
    }

    /**
     * @param JobRequest $request
     * @return mixed
     */
    public function getPotentialJobs(JobRequest $request)
    {
        if($data = $request->validated()) {
            $response = $this->repository->getPotentialJobs($data->__authenticatedUser);
            if($response){
                return response($response);
            }
            return null;
        }
    }

    public function distanceFeed(DistanceRequest $request)
    {
        if($data = $request->validated()){
            $affectedRows = Distance::where('job_id', '=', $data->jobid)->update(array('distance' => $data->distance, 'time' => $data->time));
            if($affectedRows){
                $affectedRows1 = Job::where('id', '=', $data->jobid)->update(array('admin_comments' => $data->admincomment, 'flagged' => $data->flagged, 'session_time' => $data->session, 'manually_handled' => $data->manually_handled, 'by_admin' => $data->by_admin));
                if($affectedRows1){
                    return response('Record updated!');
                }
                return response('Record not updated!');
            }
        }
    }

    public function reopen(ReOpenRequest $request)
    {
        if($data=$request->validated()){

            $response = $this->repository->reopen($data);
            if($response){
                return response($response);
            }
            return null;
        }
    }

    public function resendNotifications(NotifyRequest $request)
    {
        if($data = $request->validated()){
            if($data->notify_type == 'default'){

                $job = $this->repository->find($data['jobid']);
                $job_data = $this->repository->jobToData($job);
                $this->repository->sendNotificationTranslator($job, $job_data, '*');
            }
            else{
                $job = $this->repository->find($data['jobid']);
                $job_data = $this->repository->jobToData($job);

                try {
                    $this->repository->sendSMSNotificationToTranslator($job);
                    return response(['success' => 'SMS sent']);
                } catch (\Exception $e) {
                    return response(['success' => $e->getMessage()]);
                }
            }

            return response(['success' => 'Push sent']);
        }
    }

}
