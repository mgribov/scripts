zone "<domain>" {
    notify no;
    type slave;
    file "slave/<domain>";
    masters {
        1.2.3.4;
    };

};

