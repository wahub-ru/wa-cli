<?php

class shopContactorderviewFields extends shopBackendCustomerForm
{
  public function getData() {
    $contact = $this->getContact();
    if (!$contact) {
      $contact = new waContact();
    }
    $contact_info = array(
      'id' => $contact->getId()
    );
    foreach ($this->fields() as $field_id => $_) {
      $contact_info[$field_id] = $contact[$field_id];
    }
    $contact_info['type'] = $this->options['contact_type'];
    $fields = array();
    foreach ($this->fields() as $fid => $f) {
      if ($fid != 'address' and  $fid != 'address.shipping'
        and $fid != 'address.billing' and $fid != 'adres-dostavki') {
        $checkbox = ($f->getType() == 'Checkbox')? true : false;
        $fields[$fid] = array(
          'name'     => $f->getName(null, true),
          'required' => ($f->isRequired())? 1 : 0,
          'value'    => ($checkbox)? (($contact_info[$fid])? 'Да' : 'Нет') : $contact_info[$fid],
        );
      }
    }
    return $fields;
  }
}
